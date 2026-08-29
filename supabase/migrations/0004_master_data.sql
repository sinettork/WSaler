-- ============================================================================
-- WSaler — Master data: units, warehouses, brands, categories, suppliers,
-- customers (mirrors respective Laravel migrations).
-- ============================================================================

-- ---------------------------------------------------------------------------
-- units
-- ---------------------------------------------------------------------------
create table if not exists units (
  id bigint generated always as identity primary key,
  name text not null unique,
  short_code text not null unique,
  base boolean not null default false,
  conversion_factor_to_base numeric(12,4) not null default 1.0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create trigger trg_units_updated_at before update on units
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- warehouses (+ address FKs). profiles.branch_id FK is added here since
-- warehouses must exist first.
-- ---------------------------------------------------------------------------
create table if not exists warehouses (
  id bigint generated always as identity primary key,
  name text not null,
  code text not null unique,
  address text,
  province_id bigint references provinces (id) on delete set null,
  district_id bigint references districts (id) on delete set null,
  commune_id bigint references communes (id) on delete set null,
  village_id bigint references villages (id) on delete set null,
  phone text,
  is_default boolean not null default false,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz
);
create trigger trg_warehouses_updated_at before update on warehouses
  for each row execute function set_updated_at();

alter table profiles
  add constraint profiles_branch_id_fkey
  foreign key (branch_id) references warehouses (id) on delete set null;

-- ---------------------------------------------------------------------------
-- brands
-- ---------------------------------------------------------------------------
create table if not exists brands (
  id bigint generated always as identity primary key,
  name text not null,
  slug text not null unique,
  description text,
  logo text,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz
);
create index if not exists idx_brands_name on brands using gin (name gin_trgm_ops);
create trigger trg_brands_updated_at before update on brands
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- categories (self-referencing tree)
-- ---------------------------------------------------------------------------
create table if not exists categories (
  id bigint generated always as identity primary key,
  name text not null,
  slug text not null unique,
  description text,
  parent_id bigint references categories (id) on delete set null,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz
);
create index if not exists idx_categories_name on categories using gin (name gin_trgm_ops);
create index if not exists idx_categories_parent on categories (parent_id);
create trigger trg_categories_updated_at before update on categories
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- suppliers (+ address FKs)
-- ---------------------------------------------------------------------------
create table if not exists suppliers (
  id bigint generated always as identity primary key,
  name text not null,
  contact_person text,
  email text,
  phone text,
  address text,
  province_id bigint references provinces (id) on delete set null,
  district_id bigint references districts (id) on delete set null,
  commune_id bigint references communes (id) on delete set null,
  village_id bigint references villages (id) on delete set null,
  tax_number text,
  payment_terms text,
  notes text,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz
);
create index if not exists idx_suppliers_name on suppliers using gin (name gin_trgm_ops);
create index if not exists idx_suppliers_email on suppliers (email);
create trigger trg_suppliers_updated_at before update on suppliers
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- customers (+ address FKs, credit tracking)
-- ---------------------------------------------------------------------------
create table if not exists customers (
  id bigint generated always as identity primary key,
  code text not null unique,
  name text not null,
  contact_person text,
  email text,
  phone text,
  address text,
  province_id bigint references provinces (id) on delete set null,
  district_id bigint references districts (id) on delete set null,
  commune_id bigint references communes (id) on delete set null,
  village_id bigint references villages (id) on delete set null,
  type customer_type not null default 'retail',
  credit_limit numeric(12,2) not null default 0,
  current_balance numeric(12,2) not null default 0,
  payment_terms text,
  notes text,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz,
  version integer not null default 1
);
create index if not exists idx_customers_name on customers using gin (name gin_trgm_ops);
create index if not exists idx_customers_code on customers (code);
create index if not exists idx_customers_email on customers (email);
create index if not exists idx_customers_type on customers (type);
create index if not exists idx_customers_phone on customers (phone);
create trigger trg_customers_updated_at before update on customers
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- customer_assignments (salesperson <-> customer, sales performance module)
-- ---------------------------------------------------------------------------
create table if not exists customer_assignments (
  id bigint generated always as identity primary key,
  customer_id bigint not null references customers (id) on delete cascade,
  salesperson_user_id uuid not null references profiles (id) on delete cascade,
  status assignment_status not null default 'pending',
  valid_from date not null,
  valid_to date,
  notes text,
  assigned_by uuid references profiles (id) on delete set null,
  approval_id bigint references approvals (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz
);
create index if not exists idx_customer_assignments_salesperson_status on customer_assignments (salesperson_user_id, status);
create index if not exists idx_customer_assignments_customer_status on customer_assignments (customer_id, status);
create index if not exists idx_customer_assignments_valid_to on customer_assignments (valid_to);
create trigger trg_customer_assignments_updated_at before update on customer_assignments
  for each row execute function set_updated_at();

-- RLS: enable on all above; policies defined in 0009_rls_policies.sql
alter table units enable row level security;
alter table warehouses enable row level security;
alter table brands enable row level security;
alter table categories enable row level security;
alter table suppliers enable row level security;
alter table customers enable row level security;
alter table customer_assignments enable row level security;
