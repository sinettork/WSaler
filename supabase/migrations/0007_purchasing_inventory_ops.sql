-- ============================================================================
-- WSaler — Purchasing & inventory operations: purchase orders/receipts,
-- stock transfers, stock adjustments, supplier payments, expenses.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- purchase_orders / purchase_order_items (with generic approval link)
-- ---------------------------------------------------------------------------
create table if not exists purchase_orders (
  id bigint generated always as identity primary key,
  supplier_id bigint not null references suppliers (id) on delete restrict,
  warehouse_id bigint not null references warehouses (id) on delete restrict,
  user_id uuid not null references profiles (id) on delete restrict,
  reference_number text not null unique,
  status document_status not null default 'draft',
  order_date date not null,
  expected_date date,
  notes text,
  subtotal numeric(15,4) not null default 0,
  tax_amount numeric(15,4) not null default 0,
  discount_amount numeric(15,4) not null default 0,
  total_amount numeric(15,4) not null default 0,
  approval_id bigint references approvals (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create trigger trg_purchase_orders_updated_at before update on purchase_orders
  for each row execute function set_updated_at();

create table if not exists purchase_order_items (
  id bigint generated always as identity primary key,
  purchase_order_id bigint not null references purchase_orders (id) on delete cascade,
  product_id bigint not null references products (id) on delete restrict,
  variation_id bigint references product_variations (id) on delete set null,
  quantity integer not null,
  unit_cost numeric(15,4) not null,
  line_total numeric(15,4) not null,
  received_quantity integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_po_items_po on purchase_order_items (purchase_order_id);

-- ---------------------------------------------------------------------------
-- purchase_receipts / purchase_receipt_items — goods received (creates batches)
-- ---------------------------------------------------------------------------
create table if not exists purchase_receipts (
  id bigint generated always as identity primary key,
  supplier_id bigint references suppliers (id) on delete set null,
  warehouse_id bigint not null references warehouses (id) on delete restrict,
  user_id uuid not null references profiles (id) on delete restrict,
  purchase_order_id bigint references purchase_orders (id) on delete set null,
  reference_number text,
  status document_status not null default 'completed',
  received_at timestamptz not null,
  notes text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create trigger trg_purchase_receipts_updated_at before update on purchase_receipts
  for each row execute function set_updated_at();

create table if not exists purchase_receipt_items (
  id bigint generated always as identity primary key,
  purchase_receipt_id bigint not null references purchase_receipts (id) on delete cascade,
  product_id bigint not null references products (id) on delete restrict,
  variation_id bigint references product_variations (id) on delete restrict,
  quantity integer not null,
  unit_cost numeric(12,4) not null default 0,
  line_total numeric(12,4) not null default 0,
  batch_id bigint references batches (id) on delete set null, -- link to the batch this receipt created
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_pr_items_receipt on purchase_receipt_items (purchase_receipt_id);

-- ---------------------------------------------------------------------------
-- stock_transfers / stock_transfer_items — warehouse-to-warehouse moves
-- ---------------------------------------------------------------------------
create table if not exists stock_transfers (
  id bigint generated always as identity primary key,
  source_warehouse_id bigint not null references warehouses (id) on delete restrict,
  destination_warehouse_id bigint not null references warehouses (id) on delete restrict,
  user_id uuid not null references profiles (id) on delete restrict,
  reference_number text,
  status document_status not null default 'completed',
  notes text,
  transferred_at timestamptz not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint stock_transfers_different_warehouses check (source_warehouse_id <> destination_warehouse_id)
);
create trigger trg_stock_transfers_updated_at before update on stock_transfers
  for each row execute function set_updated_at();

create table if not exists stock_transfer_items (
  id bigint generated always as identity primary key,
  stock_transfer_id bigint not null references stock_transfers (id) on delete cascade,
  product_id bigint not null references products (id) on delete restrict,
  variation_id bigint references product_variations (id) on delete restrict,
  quantity integer not null,
  source_batch_id bigint references batches (id) on delete set null,
  destination_batch_id bigint references batches (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- ---------------------------------------------------------------------------
-- stock_adjustments / stock_adjustment_items — manual +/- corrections
-- ---------------------------------------------------------------------------
create table if not exists stock_adjustments (
  id bigint generated always as identity primary key,
  warehouse_id bigint not null references warehouses (id) on delete restrict,
  user_id uuid not null references profiles (id) on delete restrict,
  reference_number text,
  reason text,
  status document_status not null default 'completed',
  adjusted_at timestamptz not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create trigger trg_stock_adjustments_updated_at before update on stock_adjustments
  for each row execute function set_updated_at();

create table if not exists stock_adjustment_items (
  id bigint generated always as identity primary key,
  stock_adjustment_id bigint not null references stock_adjustments (id) on delete cascade,
  product_id bigint not null references products (id) on delete restrict,
  variation_id bigint references product_variations (id) on delete restrict,
  batch_id bigint references batches (id) on delete set null,
  quantity integer not null,
  type text not null default 'decrease' check (type in ('increase','decrease')),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- ---------------------------------------------------------------------------
-- supplier_payments
-- ---------------------------------------------------------------------------
create table if not exists supplier_payments (
  id bigint generated always as identity primary key,
  supplier_id bigint not null references suppliers (id) on delete restrict,
  purchase_order_id bigint references purchase_orders (id) on delete set null,
  purchase_receipt_id bigint references purchase_receipts (id) on delete set null,
  user_id uuid not null references profiles (id) on delete restrict,
  reference_number text not null unique,
  amount numeric(15,4) not null,
  payment_method text not null,
  payment_date date not null,
  reference text,
  notes text,
  status document_status not null default 'completed',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create trigger trg_supplier_payments_updated_at before update on supplier_payments
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- expense_categories / expenses
-- ---------------------------------------------------------------------------
create table if not exists expense_categories (
  id bigint generated always as identity primary key,
  name text not null,
  code text not null unique,
  description text,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists expenses (
  id bigint generated always as identity primary key,
  expense_category_id bigint not null references expense_categories (id) on delete restrict,
  warehouse_id bigint references warehouses (id) on delete set null,
  user_id uuid not null references profiles (id) on delete restrict,
  reference_number text not null unique,
  status document_status not null default 'draft',
  expense_date date not null,
  amount numeric(15,4) not null,
  currency text not null default 'USD',
  exchange_rate numeric(15,6) not null default 1,
  payment_method text,
  payment_date date,
  reference text,
  notes text,
  approval_id bigint references approvals (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create trigger trg_expenses_updated_at before update on expenses
  for each row execute function set_updated_at();

alter table purchase_orders enable row level security;
alter table purchase_order_items enable row level security;
alter table purchase_receipts enable row level security;
alter table purchase_receipt_items enable row level security;
alter table stock_transfers enable row level security;
alter table stock_transfer_items enable row level security;
alter table stock_adjustments enable row level security;
alter table stock_adjustment_items enable row level security;
alter table supplier_payments enable row level security;
alter table expense_categories enable row level security;
alter table expenses enable row level security;
