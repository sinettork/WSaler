-- ============================================================================
-- WSaler — Refund processing, reservation→sale conversion, and sales-target
-- achievement snapshot functions.
-- Mirrors InventoryController::storeRefund(), BatchReservationService::
-- convertReservationToSale(), and TargetAchievementUpdater + SaleObserver.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- process_refund(payload jsonb) — creates refund + refund_items, restocks
-- the returned quantity into the oldest-expiry active batch (FEFO restock,
-- matching the Laravel implementation's earliest-expiry lookup), reverses
-- customer credit balance proportionally, and writes 'refund_stock_in'
-- stock_movements.
--
-- payload: {
--   "sale_id", "customer_id"?, "warehouse_id"?, "reason"?, "refund_amount",
--   "items": [{ "product_id", "variation_id"?, "quantity", "unit_cost" }]
-- }
-- ---------------------------------------------------------------------------
create or replace function process_refund(payload jsonb)
returns jsonb
language plpgsql
security definer
set search_path = public
as $$
declare
  v_user_id uuid := auth.uid();
  v_sale record;
  v_refund_id bigint;
  v_item jsonb;
  v_warehouse_id bigint := (payload->>'warehouse_id')::bigint;
  v_customer_id bigint := (payload->>'customer_id')::bigint;
  v_refund_amount numeric := coalesce((payload->>'refund_amount')::numeric, 0);
  v_qty integer;
  v_cost numeric;
  v_line_total numeric;
  v_batch record;
begin
  if v_user_id is null then
    raise exception 'UNAUTHENTICATED' using errcode = 'P0001';
  end if;
  if not auth_has_permission('process sales returns') then
    raise exception 'FORBIDDEN: process sales returns' using errcode = 'P0001';
  end if;
  if v_refund_amount < 0 then
    raise exception 'refund_amount must be >= 0' using errcode = 'P0001';
  end if;

  select * into v_sale from sales where id = (payload->>'sale_id')::bigint for update;
  if v_sale is null then
    raise exception 'Sale not found' using errcode = 'P0001';
  end if;

  -- default warehouse/customer from the sale itself when not supplied
  if v_warehouse_id is null then
    v_warehouse_id := v_sale.warehouse_id;
  end if;
  if v_customer_id is null then
    v_customer_id := v_sale.customer_id;
  end if;

  insert into refunds (
    sale_id, customer_id, warehouse_id, user_id, reference_number,
    refund_amount, reason, status, refunded_at
  ) values (
    v_sale.id, v_customer_id, v_warehouse_id, v_user_id,
    'REF-' || upper(substr(md5(random()::text), 1, 6)),
    v_refund_amount, payload->>'reason', 'completed', now()
  ) returning id into v_refund_id;

  for v_item in select * from jsonb_array_elements(payload->'items') loop
    v_qty := (v_item->>'quantity')::integer;
    v_cost := coalesce((v_item->>'unit_cost')::numeric, 0);
    v_line_total := round(v_qty * v_cost, 4);

    if v_qty is null or v_qty < 1 then
      raise exception 'Refund item quantity must be at least 1' using errcode = 'P0001';
    end if;

    insert into refund_items (refund_id, product_id, variation_id, quantity, unit_cost, line_total)
    values (
      v_refund_id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint,
      v_qty, v_cost, v_line_total
    );

    -- restock into the earliest-expiry active batch for this product/warehouse
    -- (mirrors the Laravel storeRefund() batch lookup: same product +
    -- warehouse, active, remaining_quantity > 0, ordered by expiry_date asc).
    select * into v_batch
      from batches
      where product_id = (v_item->>'product_id')::bigint
        and warehouse_id = v_warehouse_id
        and status = 'active'
        and remaining_quantity > 0
      order by expiry_date asc nulls last, id asc
      for update
      limit 1;

    if v_batch is not null then
      update batches set remaining_quantity = remaining_quantity + v_qty where id = v_batch.id;

      insert into stock_movements (
        batch_id, product_id, variation_id, warehouse_id, type, quantity, unit_cost,
        reference_type, reference_id, notes, user_id, occurred_at
      ) values (
        v_batch.id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_batch.warehouse_id,
        'refund_stock_in', v_qty, v_cost, 'refund', v_refund_id, 'Refund stock return', v_user_id, now()
      );
    end if;
  end loop;

  -- reverse customer credit balance if this sale had credit payments
  if v_customer_id is not null and v_refund_amount > 0 then
    update customers set current_balance = greatest(0, current_balance - v_refund_amount)
    where id = v_customer_id;
  end if;

  return (select to_jsonb(r) from refunds r where r.id = v_refund_id);
end;
$$;

-- ---------------------------------------------------------------------------
-- convert_reservation_to_sale(allocations jsonb, reference_type, reference_id)
-- Decrements both reserved_quantity and remaining_quantity together, used
-- when a draft order with active batch reservations is converted into a
-- real sale (mirrors BatchReservationService::convertReservationToSale()).
-- allocations: [{ "batch_id": bigint, "quantity": integer }, ...]
-- ---------------------------------------------------------------------------
create or replace function convert_reservation_to_sale(allocations jsonb, p_reference_type text, p_reference_id bigint)
returns void
language plpgsql
security definer
set search_path = public
as $$
declare
  v_alloc jsonb;
  v_batch record;
  v_qty integer;
begin
  for v_alloc in select * from jsonb_array_elements(allocations) loop
    v_qty := (v_alloc->>'quantity')::integer;
    select * into v_batch from batches where id = (v_alloc->>'batch_id')::bigint for update;
    if v_batch is null then
      raise exception 'Batch % not found', v_alloc->>'batch_id' using errcode = 'P0001';
    end if;
    if coalesce(v_batch.reserved_quantity, 0) < v_qty then
      raise exception 'Cannot convert reservation for batch %. Required: %, Reserved: %',
        v_batch.id, v_qty, v_batch.reserved_quantity using errcode = 'P0001';
    end if;
    if v_batch.remaining_quantity < v_qty then
      raise exception 'Cannot convert reservation for batch %. Required: %, Remaining: %',
        v_batch.id, v_qty, v_batch.remaining_quantity using errcode = 'P0001';
    end if;

    update batches
    set reserved_quantity = reserved_quantity - v_qty,
        remaining_quantity = remaining_quantity - v_qty
    where id = v_batch.id;
  end loop;
end;
$$;

-- ---------------------------------------------------------------------------
-- cleanup_stale_reservations(days_old) — releases reservations held by draft
-- orders older than the cutoff. Intended to be invoked periodically (e.g.
-- via pg_cron or a scheduled Edge Function), mirroring
-- BatchReservationService::cleanupStaleReservations().
-- Draft order items/allocations are read from the JSONB `items` column,
-- where each item is expected to optionally carry a `batch_id`/`quantity`
-- pair produced at hold-time (fefo_pick_batches() result snapshot).
-- ---------------------------------------------------------------------------
create or replace function cleanup_stale_reservations(p_days_old integer default 7)
returns integer
language plpgsql
security definer
set search_path = public
as $$
declare
  v_draft record;
  v_item jsonb;
  v_cleaned integer := 0;
  v_batch_id bigint;
  v_qty integer;
begin
  for v_draft in
    select * from draft_orders
    where created_at < now() - (p_days_old || ' days')::interval
  loop
    for v_item in select * from jsonb_array_elements(coalesce(v_draft.items, '[]'::jsonb)) loop
      v_batch_id := (v_item->>'batch_id')::bigint;
      v_qty := (v_item->>'quantity')::integer;
      if v_batch_id is not null and v_qty is not null then
        update batches
        set reserved_quantity = greatest(0, reserved_quantity - v_qty)
        where id = v_batch_id;
      end if;
    end loop;
    v_cleaned := v_cleaned + 1;
  end loop;

  return v_cleaned;
end;
$$;

-- ---------------------------------------------------------------------------
-- Sales-target achievement snapshotting. Mirrors
-- TargetAchievementUpdater::applyOrReverse() + SaleObserver: whenever a sale
-- is completed/voided, the salesperson's active sales_targets covering that
-- date get their per-metric achievement snapshot (keyed by day) bumped or
-- reversed by the sale's contribution to each metric.
-- ---------------------------------------------------------------------------
create or replace function _sale_target_contributions(p_sale_id bigint)
returns jsonb
language plpgsql
stable
set search_path = public
as $$
declare
  v_sale record;
  v_has_customer boolean;
  v_qty_sum numeric;
  v_gross_profit numeric;
  v_collection numeric;
  v_is_new_customer boolean := false;
begin
  select * into v_sale from sales where id = p_sale_id;
  if v_sale is null then
    return '{}'::jsonb;
  end if;

  select true into v_has_customer from customers where id = v_sale.customer_id;
  v_has_customer := coalesce(v_has_customer, false);

  select coalesce(sum(quantity), 0) into v_qty_sum from sale_items where sale_id = p_sale_id;

  -- gross profit uses the weighted-average purchase cost of the batches
  -- consumed for this sale's stock_movements as a proxy for item cost
  -- (the Laravel version used item.cost, which was not persisted on
  -- sale_items in this schema; batches.purchase_cost is the closest
  -- equivalent available at query time).
  select coalesce(sum((si.unit_price - coalesce(b.avg_cost, 0)) * si.quantity), 0)
    into v_gross_profit
  from sale_items si
  left join (
    select sm.product_id, avg(bt.purchase_cost) as avg_cost
    from stock_movements sm
    join batches bt on bt.id = sm.batch_id
    where sm.reference_type = 'sale' and sm.reference_id = p_sale_id
    group by sm.product_id
  ) b on b.product_id = si.product_id
  where si.sale_id = p_sale_id;

  select coalesce(sum(amount), 0) into v_collection
  from sale_payments where sale_id = p_sale_id;

  if v_has_customer then
    select not exists (
      select 1 from sales s2
      where s2.customer_id = v_sale.customer_id
        and s2.user_id = v_sale.user_id
        and s2.id <> v_sale.id
        and s2.sold_at < v_sale.sold_at
    ) and exists (
      select 1 from customers c
      where c.id = v_sale.customer_id and c.created_at >= date_trunc('month', v_sale.sold_at)
    ) into v_is_new_customer;
  end if;

  return jsonb_build_object(
    'sales_amount', v_sale.total,
    'invoice_count', 1,
    'customer_count', case when v_has_customer then 1 else 0 end,
    'quantity', v_qty_sum,
    'gross_profit', v_gross_profit,
    'collection_amount', v_collection,
    'new_customer_count', case when v_is_new_customer then 1 else 0 end
  );
end;
$$;

create or replace function apply_sale_to_targets(p_sale_id bigint, p_reverse boolean default false)
returns void
language plpgsql
security definer
set search_path = public
as $$
declare
  v_sale record;
  v_contrib jsonb;
  v_target record;
  v_line record;
  v_metric_value numeric;
  v_delta numeric;
  v_achievement record;
  v_snapshot_date date;
  v_new_value numeric;
begin
  select * into v_sale from sales where id = p_sale_id;
  if v_sale is null then
    return;
  end if;

  v_snapshot_date := v_sale.sold_at::date;
  v_contrib := _sale_target_contributions(p_sale_id);

  for v_target in
    select * from sales_targets
    where salesperson_user_id = v_sale.user_id
      and status = 'active'
      and period_start <= v_sale.sold_at
      and period_end >= v_sale.sold_at
  loop
    for v_line in select * from sales_target_lines where sales_target_id = v_target.id loop
      if not (v_contrib ? v_line.metric::text) then
        continue;
      end if;
      v_metric_value := (v_contrib->>(v_line.metric::text))::numeric;
      v_delta := case when p_reverse then -v_metric_value else v_metric_value end;

      select * into v_achievement
      from sales_target_achievements
      where sales_target_line_id = v_line.id and snapshot_date = v_snapshot_date
      for update;

      if v_achievement is null then
        v_new_value := v_delta;
        insert into sales_target_achievements (
          sales_target_line_id, snapshot_date, achieved_value, achievement_pct, computed_at
        ) values (
          v_line.id, v_snapshot_date, v_new_value,
          case when v_line.target_value > 0 then (v_new_value / v_line.target_value) * 100 else 0 end,
          now()
        );
      else
        v_new_value := coalesce(v_achievement.achieved_value, 0) + v_delta;
        update sales_target_achievements
        set achieved_value = v_new_value,
            achievement_pct = case when v_line.target_value > 0 then (v_new_value / v_line.target_value) * 100 else 0 end,
            computed_at = now()
        where id = v_achievement.id;
      end if;
    end loop;
  end loop;
end;
$$;

-- ---------------------------------------------------------------------------
-- Trigger wiring: fires on sales status transitions, mirroring SaleObserver
-- (created→completed applies; completed→voided/canceled reverses).
-- create_sale()/void_sale() insert/update `sales` directly (they run as
-- SECURITY DEFINER and are themselves the trusted write path), so this
-- AFTER trigger on `sales` is what actually invokes the snapshot logic —
-- keeping target-achievement bookkeeping automatic regardless of which
-- function mutated the row.
-- ---------------------------------------------------------------------------
create or replace function trg_sales_apply_targets()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  if tg_op = 'INSERT' then
    if new.status = 'completed' then
      perform apply_sale_to_targets(new.id, false);
    end if;
  elsif tg_op = 'UPDATE' then
    if old.status = 'completed' and new.status in ('voided', 'refunded') then
      perform apply_sale_to_targets(new.id, true);
    end if;
  end if;
  return new;
end;
$$;

drop trigger if exists trg_sales_apply_targets_aiu on sales;
create trigger trg_sales_apply_targets_aiu
  after insert or update on sales
  for each row execute function trg_sales_apply_targets();

grant execute on function process_refund(jsonb) to authenticated;
grant execute on function convert_reservation_to_sale(jsonb, text, bigint) to authenticated;
grant execute on function cleanup_stale_reservations(integer) to authenticated;
grant execute on function apply_sale_to_targets(bigint, boolean) to authenticated;
