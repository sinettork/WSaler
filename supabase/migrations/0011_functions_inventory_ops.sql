-- ============================================================================
-- WSaler — Inventory operation functions: batch reservation, purchase
-- receiving, stock transfer, stock adjustment, unit conversion.
-- Mirrors BatchReservationService.php, InventoryController.php, UnitConverter.php
-- ============================================================================

-- ---------------------------------------------------------------------------
-- reserve_batch_stock(allocations jsonb, reference_type, reference_id)
-- allocations: [{ "batch_id": bigint, "quantity": integer }, ...]
-- ---------------------------------------------------------------------------
create or replace function reserve_batch_stock(allocations jsonb, p_reference_type text, p_reference_id bigint)
returns void
language plpgsql
security definer
set search_path = public
as $$
declare
  v_alloc jsonb;
  v_batch record;
  v_available integer;
begin
  for v_alloc in select * from jsonb_array_elements(allocations) loop
    select * into v_batch from batches where id = (v_alloc->>'batch_id')::bigint for update;
    if v_batch is null then
      raise exception 'Batch % not found', v_alloc->>'batch_id' using errcode = 'P0001';
    end if;

    v_available := v_batch.remaining_quantity - coalesce(v_batch.reserved_quantity, 0);
    if v_available < (v_alloc->>'quantity')::integer then
      raise exception 'Cannot reserve % units from batch %. Only % available.',
        v_alloc->>'quantity', v_alloc->>'batch_id', v_available using errcode = 'P0001';
    end if;

    update batches set reserved_quantity = reserved_quantity + (v_alloc->>'quantity')::integer
    where id = (v_alloc->>'batch_id')::bigint;
  end loop;
end;
$$;

-- ---------------------------------------------------------------------------
-- release_batch_reservation(allocations jsonb, reference_type, reference_id)
-- ---------------------------------------------------------------------------
create or replace function release_batch_reservation(allocations jsonb, p_reference_type text, p_reference_id bigint)
returns void
language plpgsql
security definer
set search_path = public
as $$
declare
  v_alloc jsonb;
  v_batch record;
  v_release integer;
begin
  for v_alloc in select * from jsonb_array_elements(allocations) loop
    select * into v_batch from batches where id = (v_alloc->>'batch_id')::bigint for update;
    if v_batch is null then
      raise exception 'Batch % not found', v_alloc->>'batch_id' using errcode = 'P0001';
    end if;

    v_release := least(coalesce(v_batch.reserved_quantity, 0), (v_alloc->>'quantity')::integer);
    if v_release > 0 then
      update batches set reserved_quantity = reserved_quantity - v_release
      where id = (v_alloc->>'batch_id')::bigint;
    end if;
  end loop;
end;
$$;

-- ---------------------------------------------------------------------------
-- receive_purchase(payload jsonb) — creates purchase_receipt + items + batches
-- + stock_movements ('purchase'). Optionally links to a purchase_order.
--
-- payload: {
--   "supplier_id"?, "warehouse_id", "purchase_order_id"?, "reference_number"?,
--   "notes"?, "received_at"?,
--   "items": [{ "product_id", "variation_id"?, "quantity", "unit_cost",
--               "batch_number", "manufacture_date"?, "expiry_date"? }]
-- }
-- ---------------------------------------------------------------------------
create or replace function receive_purchase(payload jsonb)
returns jsonb
language plpgsql
security definer
set search_path = public
as $$
declare
  v_user_id uuid := auth.uid();
  v_receipt_id bigint;
  v_item jsonb;
  v_batch_id bigint;
  v_warehouse_id bigint := (payload->>'warehouse_id')::bigint;
  v_received_at timestamptz := coalesce((payload->>'received_at')::timestamptz, now());
  v_qty integer;
  v_cost numeric;
  v_line_total numeric;
begin
  if v_user_id is null then
    raise exception 'UNAUTHENTICATED' using errcode = 'P0001';
  end if;
  if not auth_has_permission('receive goods') then
    raise exception 'FORBIDDEN: receive goods' using errcode = 'P0001';
  end if;

  insert into purchase_receipts (
    supplier_id, warehouse_id, user_id, purchase_order_id, reference_number, received_at, notes
  ) values (
    (payload->>'supplier_id')::bigint, v_warehouse_id, v_user_id,
    (payload->>'purchase_order_id')::bigint, payload->>'reference_number', v_received_at, payload->>'notes'
  ) returning id into v_receipt_id;

  for v_item in select * from jsonb_array_elements(payload->'items') loop
    v_qty := (v_item->>'quantity')::integer;
    v_cost := (v_item->>'unit_cost')::numeric;
    v_line_total := round(v_qty * v_cost, 4);

    insert into batches (
      batch_number, product_id, variation_id, warehouse_id, supplier_id,
      quantity, remaining_quantity, purchase_cost, manufacture_date, expiry_date,
      received_date, status
    ) values (
      coalesce(v_item->>'batch_number', 'BATCH-' || to_char(now(), 'YYYYMMDDHH24MISS') || '-' || v_receipt_id),
      (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_warehouse_id,
      (payload->>'supplier_id')::bigint,
      v_qty, v_qty, v_cost,
      (v_item->>'manufacture_date')::date, (v_item->>'expiry_date')::date,
      v_received_at::date, 'active'
    ) returning id into v_batch_id;

    insert into purchase_receipt_items (
      purchase_receipt_id, product_id, variation_id, quantity, unit_cost, line_total, batch_id
    ) values (
      v_receipt_id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint,
      v_qty, v_cost, v_line_total, v_batch_id
    );

    insert into stock_movements (
      batch_id, product_id, variation_id, warehouse_id, type, quantity, unit_cost,
      reference_type, reference_id, user_id, occurred_at
    ) values (
      v_batch_id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_warehouse_id,
      'purchase', v_qty, v_cost, 'purchase_receipt', v_receipt_id, v_user_id, v_received_at
    );

    if (payload->>'purchase_order_id') is not null and (v_item->>'purchase_order_item_id') is not null then
      update purchase_order_items set received_quantity = received_quantity + v_qty
      where id = (v_item->>'purchase_order_item_id')::bigint;
    end if;
  end loop;

  return (select to_jsonb(pr) from purchase_receipts pr where pr.id = v_receipt_id);
end;
$$;

-- ---------------------------------------------------------------------------
-- transfer_stock(payload jsonb) — moves stock between warehouses.
-- Deducts source batches FEFO, creates a new batch at destination per line.
--
-- payload: {
--   "source_warehouse_id", "destination_warehouse_id", "reference_number"?,
--   "notes"?, "transferred_at"?,
--   "items": [{ "product_id", "variation_id"?, "quantity" }]
-- }
-- ---------------------------------------------------------------------------
create or replace function transfer_stock(payload jsonb)
returns jsonb
language plpgsql
security definer
set search_path = public
as $$
declare
  v_user_id uuid := auth.uid();
  v_transfer_id bigint;
  v_item jsonb;
  v_source_wh bigint := (payload->>'source_warehouse_id')::bigint;
  v_dest_wh bigint := (payload->>'destination_warehouse_id')::bigint;
  v_transferred_at timestamptz := coalesce((payload->>'transferred_at')::timestamptz, now());
  v_alloc record;
  v_dest_batch_id bigint;
  v_src_batch record;
begin
  if v_user_id is null then
    raise exception 'UNAUTHENTICATED' using errcode = 'P0001';
  end if;
  if not auth_has_permission('stock transfer') then
    raise exception 'FORBIDDEN: stock transfer' using errcode = 'P0001';
  end if;
  if v_source_wh = v_dest_wh then
    raise exception 'Source and destination warehouse must differ' using errcode = 'P0001';
  end if;

  insert into stock_transfers (source_warehouse_id, destination_warehouse_id, user_id, reference_number, notes, transferred_at)
  values (v_source_wh, v_dest_wh, v_user_id, payload->>'reference_number', payload->>'notes', v_transferred_at)
  returning id into v_transfer_id;

  for v_item in select * from jsonb_array_elements(payload->'items') loop
    for v_alloc in
      select * from fefo_pick_batches(
        (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_source_wh, (v_item->>'quantity')::integer
      )
    loop
      select * into v_src_batch from batches where id = v_alloc.batch_id;

      update batches set remaining_quantity = remaining_quantity - v_alloc.quantity where id = v_alloc.batch_id;
      insert into stock_movements (batch_id, product_id, variation_id, warehouse_id, type, quantity, reference_type, reference_id, user_id, occurred_at)
      values (v_alloc.batch_id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_source_wh, 'transfer_out', -v_alloc.quantity, 'stock_transfer', v_transfer_id, v_user_id, v_transferred_at);

      insert into batches (
        batch_number, product_id, variation_id, warehouse_id, supplier_id,
        quantity, remaining_quantity, purchase_cost, manufacture_date, expiry_date, received_date, status
      ) values (
        v_src_batch.batch_number || '-T' || v_transfer_id, v_src_batch.product_id, v_src_batch.variation_id, v_dest_wh,
        v_src_batch.supplier_id, v_alloc.quantity, v_alloc.quantity, v_src_batch.purchase_cost,
        v_src_batch.manufacture_date, v_src_batch.expiry_date, v_transferred_at::date, 'active'
      ) returning id into v_dest_batch_id;

      insert into stock_movements (batch_id, product_id, variation_id, warehouse_id, type, quantity, reference_type, reference_id, user_id, occurred_at)
      values (v_dest_batch_id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_dest_wh, 'transfer_in', v_alloc.quantity, 'stock_transfer', v_transfer_id, v_user_id, v_transferred_at);

      insert into stock_transfer_items (stock_transfer_id, product_id, variation_id, quantity, source_batch_id, destination_batch_id)
      values (v_transfer_id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_alloc.quantity, v_alloc.batch_id, v_dest_batch_id);
    end loop;
  end loop;

  return (select to_jsonb(t) from stock_transfers t where t.id = v_transfer_id);
end;
$$;

-- ---------------------------------------------------------------------------
-- adjust_stock(payload jsonb) — manual +/- correction against a specific batch
--
-- payload: {
--   "warehouse_id", "reference_number"?, "reason"?, "adjusted_at"?,
--   "items": [{ "product_id", "variation_id"?, "batch_id", "quantity", "type": "increase"|"decrease" }]
-- }
-- ---------------------------------------------------------------------------
create or replace function adjust_stock(payload jsonb)
returns jsonb
language plpgsql
security definer
set search_path = public
as $$
declare
  v_user_id uuid := auth.uid();
  v_adjustment_id bigint;
  v_item jsonb;
  v_warehouse_id bigint := (payload->>'warehouse_id')::bigint;
  v_adjusted_at timestamptz := coalesce((payload->>'adjusted_at')::timestamptz, now());
  v_qty integer;
  v_type text;
  v_signed_qty integer;
  v_batch record;
begin
  if v_user_id is null then
    raise exception 'UNAUTHENTICATED' using errcode = 'P0001';
  end if;
  if not auth_has_permission('stock adjustment') then
    raise exception 'FORBIDDEN: stock adjustment' using errcode = 'P0001';
  end if;

  insert into stock_adjustments (warehouse_id, user_id, reference_number, reason, adjusted_at)
  values (v_warehouse_id, v_user_id, payload->>'reference_number', payload->>'reason', v_adjusted_at)
  returning id into v_adjustment_id;

  for v_item in select * from jsonb_array_elements(payload->'items') loop
    v_qty := (v_item->>'quantity')::integer;
    v_type := coalesce(v_item->>'type', 'decrease');
    v_signed_qty := case when v_type = 'increase' then v_qty else -v_qty end;

    select * into v_batch from batches where id = (v_item->>'batch_id')::bigint for update;
    if v_batch is null then
      raise exception 'Batch % not found', v_item->>'batch_id' using errcode = 'P0001';
    end if;
    if v_type = 'decrease' and v_batch.remaining_quantity < v_qty then
      raise exception 'Insufficient stock in batch % for decrease of %', v_batch.id, v_qty using errcode = 'P0001';
    end if;

    update batches set remaining_quantity = remaining_quantity + v_signed_qty where id = v_batch.id;

    insert into stock_adjustment_items (stock_adjustment_id, product_id, variation_id, batch_id, quantity, type)
    values (v_adjustment_id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_batch.id, v_qty, v_type);

    insert into stock_movements (batch_id, product_id, variation_id, warehouse_id, type, quantity, reference_type, reference_id, user_id, occurred_at)
    values (
      v_batch.id, (v_item->>'product_id')::bigint, (v_item->>'variation_id')::bigint, v_warehouse_id,
      (case when v_type = 'increase' then 'adjustment_increase' else 'adjustment_decrease' end)::stock_movement_type,
      v_signed_qty, 'stock_adjustment', v_adjustment_id, v_user_id, v_adjusted_at
    );
  end loop;

  return (select to_jsonb(a) from stock_adjustments a where a.id = v_adjustment_id);
end;
$$;

-- ---------------------------------------------------------------------------
-- convert_units(quantity, from_short_code, to_short_code) — mirrors UnitConverter
-- ---------------------------------------------------------------------------
create or replace function convert_units(p_quantity numeric, p_from_code text, p_to_code text)
returns numeric
language plpgsql
stable
as $$
declare
  v_from units;
  v_to units;
  v_from_base units;
  v_to_base units;
begin
  if p_from_code = p_to_code then
    return p_quantity;
  end if;

  select * into v_from from units where short_code = p_from_code;
  select * into v_to from units where short_code = p_to_code;
  if v_from is null then raise exception 'Unit not found: %', p_from_code using errcode = 'P0001'; end if;
  if v_to is null then raise exception 'Unit not found: %', p_to_code using errcode = 'P0001'; end if;

  if v_from.base then v_from_base := v_from;
  else select * into v_from_base from units where base = true and id <> v_from.id order by id limit 1;
  end if;

  if v_to.base then v_to_base := v_to;
  else select * into v_to_base from units where base = true and id <> v_to.id order by id limit 1;
  end if;

  if v_from_base is null or v_to_base is null or v_from_base.id <> v_to_base.id then
    raise exception 'Cannot convert between units of different families: % and %', p_from_code, p_to_code using errcode = 'P0001';
  end if;

  return (p_quantity * v_from.conversion_factor_to_base) / v_to.conversion_factor_to_base;
end;
$$;

grant execute on function reserve_batch_stock(jsonb, text, bigint) to authenticated;
grant execute on function release_batch_reservation(jsonb, text, bigint) to authenticated;
grant execute on function receive_purchase(jsonb) to authenticated;
grant execute on function transfer_stock(jsonb) to authenticated;
grant execute on function adjust_stock(jsonb) to authenticated;
grant execute on function convert_units(numeric, text, text) to authenticated;
