-- ============================================================================
-- WSaler — Products, variations, batches (FEFO), stock movements, price breaks
-- ============================================================================

create table if not exists products (
  id bigint generated always as identity primary key,
  name text not null,
  sku text not null unique,
  barcode text unique,
  description text,
  brand_id bigint references brands (id) on delete set null,
  category_id bigint references categories (id) on delete set null,
  base_unit_id bigint not null references units (id) on delete restrict,
  image text,
  retail_price numeric(12,2) not null default 0,
  wholesale_price numeric(12,2) not null default 0,
  distributor_price numeric(12,2) not null default 0,
  cost_price numeric(12,2) not null default 0,
  status product_status not null default 'active',
  track_stock boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz,
  version integer not null default 1
);
create index if not exists idx_products_name on products using gin (name gin_trgm_ops);
create index if not exists idx_products_sku on products (sku);
create index if not exists idx_products_barcode on products (barcode);
create index if not exists idx_products_status on products (status);
create index if not exists idx_products_track_stock on products (track_stock);
create trigger trg_products_updated_at before update on products
  for each row execute function set_updated_at();

create table if not exists product_variations (
  id bigint generated always as identity primary key,
  product_id bigint not null references products (id) on delete cascade,
  name text not null,
  value text not null,
  sku_suffix text,
  sku text, -- computed convenience column (product.sku || sku_suffix), kept nullable+indexed
  barcode text,
  additional_price numeric(12,2) not null default 0,
  quantity_multiplier integer not null default 1,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_product_variations_product on product_variations (product_id);
create index if not exists idx_product_variations_sku on product_variations (sku);
create trigger trg_product_variations_updated_at before update on product_variations
  for each row execute function set_updated_at();

create table if not exists product_price_breaks (
  id bigint generated always as identity primary key,
  product_id bigint not null references products (id) on delete cascade,
  customer_type customer_type,
  min_quantity integer not null,
  max_quantity integer,
  unit_price numeric(12,2) not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_price_breaks_product on product_price_breaks (product_id);
create index if not exists idx_price_breaks_qty on product_price_breaks (min_quantity, max_quantity);
create trigger trg_price_breaks_updated_at before update on product_price_breaks
  for each row execute function set_updated_at();

create table if not exists price_lists (
  id bigint generated always as identity primary key,
  name text not null,
  customer_id bigint references customers (id) on delete cascade,
  product_id bigint not null references products (id) on delete cascade,
  unit_id bigint not null references units (id) on delete restrict,
  price numeric(12,2) not null,
  min_qty integer not null default 1,
  valid_from date,
  valid_to date,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_price_lists_customer_product on price_lists (customer_id, product_id);
create index if not exists idx_price_lists_active on price_lists (is_active);
create trigger trg_price_lists_updated_at before update on price_lists
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- batches — FEFO (first-expired-first-out) stock lots.
-- remaining_quantity = quantity - sold; reserved_quantity = held for drafts.
-- ---------------------------------------------------------------------------
create table if not exists batches (
  id bigint generated always as identity primary key,
  batch_number text not null unique,
  product_id bigint not null references products (id) on delete restrict,
  variation_id bigint references product_variations (id) on delete restrict,
  warehouse_id bigint not null references warehouses (id) on delete restrict,
  supplier_id bigint references suppliers (id) on delete restrict,
  quantity integer not null,
  remaining_quantity integer not null,
  reserved_quantity integer not null default 0,
  purchase_cost numeric(12,4) not null,
  manufacture_date date,
  expiry_date date,
  received_date date not null,
  notes text,
  status batch_status not null default 'active',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz,
  version integer not null default 1,
  constraint batches_remaining_nonnegative check (remaining_quantity >= 0),
  constraint batches_reserved_nonnegative check (reserved_quantity >= 0),
  constraint batches_reserved_within_remaining check (reserved_quantity <= remaining_quantity)
);
create index if not exists idx_batches_product on batches (product_id);
create index if not exists idx_batches_expiry on batches (expiry_date);
create index if not exists idx_batches_number on batches (batch_number);
create index if not exists idx_batches_status on batches (status);
create index if not exists idx_batches_status_expiry on batches (status, expiry_date);
create index if not exists idx_batches_product_warehouse_status on batches (product_id, warehouse_id, status);
create trigger trg_batches_updated_at before update on batches
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- stock_movements — immutable audit trail of every stock change
-- ---------------------------------------------------------------------------
create table if not exists stock_movements (
  id bigint generated always as identity primary key,
  batch_id bigint not null references batches (id) on delete restrict,
  product_id bigint not null references products (id) on delete restrict,
  variation_id bigint references product_variations (id) on delete restrict,
  warehouse_id bigint not null references warehouses (id) on delete restrict,
  type stock_movement_type not null,
  quantity integer not null, -- signed: negative = stock out, positive = stock in
  unit_cost numeric(12,4),
  reference_type text, -- 'sale' | 'purchase_receipt' | 'stock_transfer' | 'stock_adjustment' | 'refund'
  reference_id bigint,
  notes text,
  user_id uuid references profiles (id) on delete set null,
  occurred_at timestamptz not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  version integer not null default 1
);
create index if not exists idx_stock_movements_product_occurred on stock_movements (product_id, occurred_at);
create index if not exists idx_stock_movements_batch_type on stock_movements (batch_id, type);
create index if not exists idx_stock_movements_reference on stock_movements (reference_type, reference_id);
create index if not exists idx_stock_movements_warehouse_occurred on stock_movements (warehouse_id, occurred_at);
create index if not exists idx_stock_movements_type on stock_movements (type);
create trigger trg_stock_movements_updated_at before update on stock_movements
  for each row execute function set_updated_at();

alter table products enable row level security;
alter table product_variations enable row level security;
alter table product_price_breaks enable row level security;
alter table price_lists enable row level security;
alter table batches enable row level security;
alter table stock_movements enable row level security;
