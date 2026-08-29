-- ============================================================================
-- WSaler — Business logic functions: FEFO batch picking, create_sale, void_sale
-- Mirrors app/Services/FefoBatchSelector.php + SaleService.php + BatchPickerService.php
-- All SECURITY DEFINER + run inside implicit function transactions; RLS on
-- underlying tables still applies to the *caller* via the permission checks
-- performed explicitly inside each function.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- next_invoice_number() — INV-YYYYMMDD-0001, sequential per day
-- ---------------------------------------------------------------------------
create or replace function next_invoice_number()
returns text
language plpgsql
as $$
declare
  prefix text := 'INV-' || to_char(now(), 'YYYYMMDD') || '-';
  last_seq integer;
  next_seq integer;
begin
  select coalesce(max((regexp_match(invoice_number, '(\d+)$'))[1]::integer), 0)
    into last_seq
    from sales
    where invoice_number like prefix || '%';
  next_seq := last_seq + 1;
  return prefix || lpad(next_seq::text, 4, '0');
end;
$$;

-- ---------------------------------------------------------------------------
-- fefo_pick_batches(product_id, variation_id, warehouse_id, quantity_needed)
-- Returns rows of (batch_id, quantity) allocated oldest-expiry-first.
-- Raises exception if insufficient stock (mirrors InsufficientStockException).
-- ---------------------------------------------------------------------------
create or replace function fefo_pick_batches(
  p_product_id bigint,
  p_variation_id bigint,
  p_warehouse_id bigint,
  p_quantity integer
)
returns table (batch_id bigint, quantity integer)
language plpgsql
as $$
declare
  rec record;
  remaining integer := p_quantity;
  total_available integer := 0;
  take integer;
begin
  for rec in
    select b.id, (b.remaining_quantity - coalesce(b.reserved_quantity, 0)) as available
    from batches b
    where b.product_id = p_product_id
      and b.status = 'active'
      and (b.remaining_quantity - coalesce(b.reserved_quantity, 0)) > 0
      and (b.expiry_date is null or b.expiry_date >= current_date)
      and (p_variation_id is null or b.variation_id = p_variation_id)
      and (p_warehouse_id is null or b.warehouse_id = p_warehouse_id)
    order by (case when b.expiry_date is null then 1 else 0 end), b.expiry_date asc, b.id asc
    for update of b
  loop
    total_available := total_available + rec.available;
    if remaining <= 0 then
      continue;
    end if;
    take := least(rec.available, remaining);
    batch_id := rec.id;
    quantity := take;
    return next;
    remaining := remaining - take;
  end loop;

  if remaining > 0 then
    raise exception 'INSUFFICIENT_STOCK: product % requested % available %', p_product_id, p_quantity, total_available
      using errcode = 'P0001';
  end if;
end;
$$;

-- ---------------------------------------------------------------------------
-- create_sale(payload jsonb) — atomic POS checkout.
--
-- payload shape:
-- {
--   "customer_id": bigint|null,
--   "warehouse_id": bigint,
--   "items": [{ "product_id", "variation_id"?, "unit_id", "quantity", "unit_price", "discount"? }],
--   "payments": [{ "method", "amount", "reference"? }],
--   "discount"?: numeric, "tax"?: numeric, "notes"?: text, "sold_at"?: timestamptz
-- }
-- ---------------------------------------------------------------------------
create or replace function create_sale(payload jsonb)
returns jsonb
language plpgsql
security definer
set search_path = public
as $$
declare
  v_user_id uuid := auth.uid();
  v_customer_id bigint := (payload->>'customer_id')::bigint;
  v_warehouse_id bigint := (payload->>'warehouse_id')::bigint;
  v_items jsonb := payload->'items';
  v_payments jsonb := payload->'payments';
  v_item jsonb;
  v_payment jsonb;
  v_qty integer;
  v_price numeric;
  v_discount numeric;
  v_line_total numeric;
  v_subtotal numeric := 0;
  v_sale_discount numeric := coalesce((payload->>'discount')::numeric, 0);
  v_tax numeric := coalesce((payload->>'tax')::numeric, 0);
  v_total numeric;
  v_paid numeric := 0;
  v_change numeric;
  v_credit_amount numeric := 0;
  v_customer record;
  v_pending_credit numeric := 0;
  v_available numeric;
  v_sale_id bigint;
  v_multiplier integer;
  v_deduction_qty integer;
  v_alloc record;
  v_now timestamptz := now();
  v_sold_at timestamptz := coalesce((payload->>'sold_at')::timestamptz, v_now);
begin
  if v_user_id is null then
    raise exception 'UNAUTHENTICATED' using errcode = 'P0001';
  end if;
  if not auth_has_permission('create invoices') then
    raise exception 'FORBIDDEN: create invoices' using errcode = 'P0001';
  end if;
  if v_warehouse_id is null then
    raise exception 'warehouse_id is required' using errcode = 'P0001';
  end if;

  -- 1. validate + compute item line totals, subtotal
  for v_item in select * from jsonb_array_elements(v_items) loop
    v_qty := (v_item->>'quantity')::integer;
    v_price := (v_item->>'unit_price')::numeric;
    v_discount := coalesce((v_item->>'discount')::numeric, 0);
    if v_qty < 1 then
      raise exception 'Quantity must be at least 1.' using errcode = 'P0001';
    end if;
    if v_price < 0 then
      raise exception 'Unit price cannot be negative.' using errcode = 'P0001';
    end if;
    v_line_total := round((v_qty * v_price) - v_discount, 2);
    v_subtotal := v_subtotal + v_line_total;
  end loop;

  v_total := round(v_subtotal - v_sale_discount + v_tax, 2);

  -- 2. payments total + credit check
  for v_payment in select * from jsonb_array_elements(v_payments) loop
    v_paid := v_paid + (v_payment->>'amount')::numeric;
    if v_payment->>'method' = 'credit' then
      v_credit_amount := v_credit_amount + (v_payment->>'amount')::numeric;
    end if;
  end loop;
  v_paid := round(v_paid, 2);
  v_change := round(greatest(0, v_paid - v_total), 2);

  if v_credit_amount > 0 and v_customer_id is null then
    raise exception 'Credit payment requires a customer.' using errcode = 'P0001';
  end if;

  if v_customer_id is not null then
    select * into v_customer from customers where id = v_customer_id for update;
    if v_customer is null then
      raise exception 'Customer not found' using errcode = 'P0001';
    end if;

    if v_credit_amount > 0 then
      select coalesce(sum((p.value->>'amount')::numeric), 0) into v_pending_credit
      from draft_orders d, jsonb_array_elements(coalesce(d.payments, '[]'::jsonb)) as p(value)
      where d.customer_id = v_customer_id and (p.value->>'method') = 'credit';

      v_available := v_customer.credit_limit - v_customer.current_balance - v_pending_credit;
      if v_customer.credit_limit > 0
         and (v_customer.current_balance + v_pending_credit + v_credit_amount) > v_customer.credit_limit then
        raise exception 'Credit sale exceeds customer credit limit. Available: %', greatest(0, v_available)
          using errcode = 'P0001';
      end if;
    end if;
  end if;

  -- 3. create sale header
  insert into sales (
    invoice_number, customer_id, warehouse_id, user_id,
    subtotal, discount, tax, total, paid, change_due,
    status, notes, sold_at
  ) values (
    next_invoice_number(), v_customer_id, v_warehouse_id, v_user_id,
    v_subtotal, v_sale_discount, v_tax, v_total, v_paid, v_change,
    'completed', payload->>'notes', v_sold_at
  ) returning id into v_sale_id;

  -- 4. sale_items + FEFO batch allocation + stock_movements + batch decrement
  for v_item in select * from jsonb_array_elements(v_items) loop
    v_qty := (v_item->>'quantity')::integer;
    v_price := (v_item->>'unit_price')::numeric;
    v_discount := coalesce((v_item->>'discount')::numeric, 0);
    v_line_total := round((v_qty * v_price) - v_discount, 2);

    insert into sale_items (sale_id, product_id, variation_id, unit_id, quantity, unit_price, discount, line_total)
    values (
      v_sale_id,
      (v_item->>'product_id')::bigint,
      (v_item->>'variation_id')::bigint,
      (v_item->>'unit_id')::bigint,
      v_qty, v_price, v_discount, v_line_total
    );

    v_multiplier := 1;
    if v_item->>'variation_id' is not null then
      select greatest(1, quantity_multiplier) into v_multiplier
      from product_variations where id = (v_item->>'variation_id')::bigint;
      v_multiplier := coalesce(v_multiplier, 1);
    end if;
    v_deduction_qty := v_qty * v_multiplier;

    for v_alloc in
      select * from fefo_pick_batches(
        (v_item->>'product_id')::bigint,
        (v_item->>'variation_id')::bigint,
        v_warehouse_id,
        v_deduction_qty
      )
    loop
      insert into stock_movements (
        batch_id, product_id, variation_id, warehouse_id, type, quantity,
        reference_type, reference_id, user_id, occurred_at
      ) values (
        v_alloc.batch_id,
        (v_item->>'product_id')::bigint,
        (v_item->>'variation_id')::bigint,
        v_warehouse_id, 'sale', -v_alloc.quantity,
        'sale', v_sale_id, v_user_id, v_now
      );

      update batches set remaining_quantity = remaining_quantity - v_alloc.quantity
      where id = v_alloc.batch_id;
    end loop;
  end loop;

  -- 5. sale_payments
  for v_payment in select * from jsonb_array_elements(v_payments) loop
    insert into sale_payments (sale_id, method, amount, reference, paid_at)
    values (
      v_sale_id,
      (v_payment->>'method')::payment_method,
      (v_payment->>'amount')::numeric,
      v_payment->>'reference',
      v_now
    );
  end loop;

  -- 6. customer credit balance
  if v_customer_id is not null and v_credit_amount > 0 then
    update customers set current_balance = current_balance + v_credit_amount where id = v_customer_id;
  end if;

  return (select to_jsonb(s) from sales s where s.id = v_sale_id);
end;
$$;

-- ---------------------------------------------------------------------------
-- void_sale(p_sale_id, p_reason) — reverses stock + customer credit
-- ---------------------------------------------------------------------------
create or replace function void_sale(p_sale_id bigint, p_reason text default null)
returns jsonb
language plpgsql
security definer
set search_path = public
as $$
declare
  v_user_id uuid := auth.uid();
  v_sale record;
  v_movement record;
  v_credit_total numeric := 0;
begin
  if v_user_id is null then
    raise exception 'UNAUTHENTICATED' using errcode = 'P0001';
  end if;
  if not auth_has_permission('cancel sales') then
    raise exception 'FORBIDDEN: cancel sales' using errcode = 'P0001';
  end if;

  select * into v_sale from sales where id = p_sale_id for update;
  if v_sale is null then
    raise exception 'Sale not found' using errcode = 'P0001';
  end if;
  if v_sale.status = 'voided' then
    raise exception 'Sale is already voided.' using errcode = 'P0001';
  end if;
  if v_sale.status <> 'completed' then
    raise exception 'Cannot void a sale with status %', v_sale.status using errcode = 'P0001';
  end if;

  for v_movement in
    select * from stock_movements where reference_type = 'sale' and reference_id = p_sale_id for update
  loop
    insert into stock_movements (
      batch_id, product_id, variation_id, warehouse_id, type, quantity,
      reference_type, reference_id, user_id, occurred_at, notes
    ) values (
      v_movement.batch_id, v_movement.product_id, v_movement.variation_id, v_movement.warehouse_id,
      'sale_void', -v_movement.quantity, 'sale', p_sale_id, v_user_id, now(),
      'Reversed by void of invoice ' || v_sale.invoice_number
    );
    update batches set remaining_quantity = remaining_quantity + abs(v_movement.quantity)
    where id = v_movement.batch_id;
  end loop;

  select coalesce(sum(amount), 0) into v_credit_total
  from sale_payments where sale_id = p_sale_id and method = 'credit';

  if v_credit_total > 0 and v_sale.customer_id is not null then
    update customers set current_balance = current_balance - v_credit_total where id = v_sale.customer_id;
  end if;

  update sales set status = 'voided', voided_at = now(), voided_by = v_user_id, void_reason = p_reason
  where id = p_sale_id;

  return (select to_jsonb(s) from sales s where s.id = p_sale_id);
end;
$$;

grant execute on function next_invoice_number() to authenticated;
grant execute on function fefo_pick_batches(bigint, bigint, bigint, integer) to authenticated;
grant execute on function create_sale(jsonb) to authenticated;
grant execute on function void_sale(bigint, text) to authenticated;
