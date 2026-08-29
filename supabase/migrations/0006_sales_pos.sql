-- ============================================================================
-- WSaler — Sales (POS + history), sale items/payments, draft orders, refunds
-- ============================================================================

create table if not exists sales (
  id bigint generated always as identity primary key,
  invoice_number text not null unique,
  customer_id bigint references customers (id) on delete set null,
  warehouse_id bigint not null references warehouses (id) on delete restrict,
  user_id uuid not null references profiles (id) on delete restrict,
  subtotal numeric(12,2) not null,
  discount numeric(12,2) not null default 0,
  tax numeric(12,2) not null default 0,
  total numeric(12,2) not null,
  paid numeric(12,2) not null,
  change_due numeric(12,2) not null default 0,
  status sale_status not null default 'completed',
  notes text,
  sold_at timestamptz not null,
  voided_at timestamptz,
  voided_by uuid references profiles (id) on delete set null,
  void_reason text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz,
  version integer not null default 1
);
create index if not exists idx_sales_customer on sales (customer_id);
create index if not exists idx_sales_warehouse on sales (warehouse_id);
create index if not exists idx_sales_user on sales (user_id);
create index if not exists idx_sales_status on sales (status);
create index if not exists idx_sales_sold_at on sales (sold_at);
create index if not exists idx_sales_invoice_number on sales (invoice_number);
create index if not exists idx_sales_warehouse_sold_at on sales (warehouse_id, sold_at);
create index if not exists idx_sales_customer_sold_at on sales (customer_id, sold_at);
create trigger trg_sales_updated_at before update on sales
  for each row execute function set_updated_at();

create table if not exists sale_items (
  id bigint generated always as identity primary key,
  sale_id bigint not null references sales (id) on delete cascade,
  product_id bigint not null references products (id) on delete restrict,
  variation_id bigint references product_variations (id) on delete set null,
  unit_id bigint not null references units (id) on delete restrict,
  quantity integer not null,
  unit_price numeric(12,2) not null,
  discount numeric(12,2) not null default 0,
  line_total numeric(12,2) not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_sale_items_sale on sale_items (sale_id);
create index if not exists idx_sale_items_product on sale_items (product_id);
create index if not exists idx_sale_items_unit on sale_items (unit_id);

create table if not exists sale_payments (
  id bigint generated always as identity primary key,
  sale_id bigint not null references sales (id) on delete cascade,
  method payment_method not null,
  amount numeric(12,2) not null,
  reference text,
  paid_at timestamptz not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_sale_payments_sale on sale_payments (sale_id);
create index if not exists idx_sale_payments_method on sale_payments (method);
create index if not exists idx_sale_payments_paid_at on sale_payments (paid_at);

-- ---------------------------------------------------------------------------
-- draft_orders — POS hold/recall. Items/payments stored as JSON snapshots,
-- exactly like the Laravel version (they are cart-shaped drafts, not
-- normalized rows, since they never touch stock until converted to a sale).
-- ---------------------------------------------------------------------------
create table if not exists draft_orders (
  id bigint generated always as identity primary key,
  name text,
  user_id uuid not null references profiles (id) on delete cascade,
  customer_id bigint references customers (id) on delete set null,
  warehouse_id bigint references warehouses (id) on delete set null,
  items jsonb not null default '[]',
  payments jsonb default '[]',
  discount numeric(12,2) not null default 0,
  tax numeric(12,2) not null default 0,
  notes text,
  subtotal numeric(12,2) not null default 0,
  total numeric(12,2) not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_draft_orders_user_updated on draft_orders (user_id, updated_at);
create trigger trg_draft_orders_updated_at before update on draft_orders
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- refunds / refund_items — sales returns
-- ---------------------------------------------------------------------------
create table if not exists refunds (
  id bigint generated always as identity primary key,
  sale_id bigint not null references sales (id) on delete cascade,
  customer_id bigint references customers (id) on delete set null,
  warehouse_id bigint references warehouses (id) on delete set null,
  user_id uuid not null references profiles (id) on delete restrict,
  reference_number text,
  refund_amount numeric(12,2) not null default 0,
  reason text,
  status document_status not null default 'completed',
  refunded_at timestamptz not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create trigger trg_refunds_updated_at before update on refunds
  for each row execute function set_updated_at();

create table if not exists refund_items (
  id bigint generated always as identity primary key,
  refund_id bigint not null references refunds (id) on delete cascade,
  product_id bigint not null references products (id) on delete restrict,
  variation_id bigint references product_variations (id) on delete restrict,
  quantity integer not null,
  unit_cost numeric(12,4) not null default 0,
  line_total numeric(12,4) not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

alter table sales enable row level security;
alter table sale_items enable row level security;
alter table sale_payments enable row level security;
alter table draft_orders enable row level security;
alter table refunds enable row level security;
alter table refund_items enable row level security;
