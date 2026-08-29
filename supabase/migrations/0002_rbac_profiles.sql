-- ============================================================================
-- WSaler — RBAC: profiles (extends auth.users), roles, permissions, teams,
-- territories. Mirrors the Laravel users/roles/permissions/model_has_roles
-- tables, but keyed to Supabase Auth's auth.users.id (uuid).
-- ============================================================================

-- ---------------------------------------------------------------------------
-- teams (sales team groupings) — created before profiles because profiles.team_id
-- references it, and team.leader_user_id references profiles (nullable FK ok).
-- ---------------------------------------------------------------------------
create table if not exists teams (
  id bigint generated always as identity primary key,
  name text not null,
  code text not null unique,
  leader_user_id uuid, -- FK to profiles added after profiles is created
  description text,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz
);
create index if not exists idx_teams_is_active on teams (is_active) where deleted_at is null;

-- ---------------------------------------------------------------------------
-- profiles — 1:1 with auth.users. Holds app-specific user attributes that
-- used to live on the Laravel `users` table (role, branch, employment status).
-- ---------------------------------------------------------------------------
create table if not exists profiles (
  id uuid primary key references auth.users (id) on delete cascade,
  name text not null,
  email text not null unique,
  role user_role not null default 'cashier',
  employment_status employment_status not null default 'active',
  branch_id bigint, -- FK to warehouses added later (avoid circular dep at creation time)
  team_id bigint references teams (id) on delete set null,
  is_active boolean not null default true,
  password_changed_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  version integer not null default 1
);
create index if not exists idx_profiles_role on profiles (role);
create index if not exists idx_profiles_name on profiles using gin (name gin_trgm_ops);

alter table teams
  add constraint teams_leader_user_id_fkey
  foreign key (leader_user_id) references profiles (id) on delete set null;

create trigger trg_profiles_updated_at before update on profiles
  for each row execute function set_updated_at();
create trigger trg_teams_updated_at before update on teams
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- permissions & role_permissions — static catalogue + role -> permission map.
-- Roles here are the `user_role` enum values themselves (no separate roles
-- table needed since roles are not user-defined in this app).
-- ---------------------------------------------------------------------------
create table if not exists permissions (
  id bigint generated always as identity primary key,
  name text not null unique,
  description text
);

create table if not exists role_permissions (
  role user_role not null,
  permission_id bigint not null references permissions (id) on delete cascade,
  primary key (role, permission_id)
);

-- Helper: does the given user (uuid) hold `permission_name`?
create or replace function auth_has_permission(permission_name text)
returns boolean
language sql
security definer
stable
as $$
  select exists (
    select 1
    from profiles p
    join role_permissions rp on rp.role = p.role
    join permissions perm on perm.id = rp.permission_id
    where p.id = auth.uid()
      and perm.name = permission_name
  )
  or exists ( -- admin is a wildcard, matching the Laravel frontend's hasPermission() shortcut
    select 1 from profiles p where p.id = auth.uid() and p.role = 'admin'
  );
$$;

create or replace function auth_has_role(role_names text[])
returns boolean
language sql
security definer
stable
as $$
  select exists (
    select 1 from profiles p
    where p.id = auth.uid() and p.role::text = any(role_names)
  );
$$;

create or replace function auth_is_admin()
returns boolean
language sql
security definer
stable
as $$
  select exists (select 1 from profiles p where p.id = auth.uid() and p.role = 'admin');
$$;

-- ---------------------------------------------------------------------------
-- territories + territory assignments (sales performance module)
-- ---------------------------------------------------------------------------
create table if not exists territories (
  id bigint generated always as identity primary key,
  name text not null,
  code text not null unique,
  region text,
  description text,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz
);
create index if not exists idx_territories_is_active on territories (is_active) where deleted_at is null;
create trigger trg_territories_updated_at before update on territories
  for each row execute function set_updated_at();

create table if not exists territory_user (
  id bigint generated always as identity primary key,
  territory_id bigint not null references territories (id) on delete cascade,
  user_id uuid not null references profiles (id) on delete cascade,
  assigned_at timestamptz not null default now(),
  assigned_by uuid references profiles (id) on delete set null,
  valid_from date not null,
  valid_to date,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (territory_id, user_id, valid_from)
);
create index if not exists idx_territory_user_valid on territory_user (user_id, valid_to);
create trigger trg_territory_user_updated_at before update on territory_user
  for each row execute function set_updated_at();

-- ---------------------------------------------------------------------------
-- activity_logs — generic audit trail
-- ---------------------------------------------------------------------------
create table if not exists activity_logs (
  id bigint generated always as identity primary key,
  user_id uuid references profiles (id) on delete cascade,
  action text not null,
  description text,
  module text,
  resource_type text,
  resource_id bigint,
  event text, -- created|updated|deleted|approved|rejected
  before jsonb,
  after jsonb,
  ip_address text,
  user_agent text,
  created_at timestamptz not null default now()
);
create index if not exists idx_activity_logs_user_action on activity_logs (user_id, action);
create index if not exists idx_activity_logs_resource on activity_logs (resource_type, resource_id);

-- ---------------------------------------------------------------------------
-- approvals — generic polymorphic approval workflow
-- ---------------------------------------------------------------------------
create table if not exists approvals (
  id bigint generated always as identity primary key,
  approvable_type text not null, -- e.g. 'purchase_order', 'stock_adjustment'
  approvable_id bigint not null,
  requested_by uuid not null references profiles (id) on delete restrict,
  approver_id uuid references profiles (id) on delete set null,
  status approval_status not null default 'pending',
  required_level text,
  notes text,
  metadata jsonb,
  requested_at timestamptz not null default now(),
  decided_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_approvals_approvable on approvals (approvable_type, approvable_id);
create index if not exists idx_approvals_status_approver on approvals (status, approver_id);
create index if not exists idx_approvals_requester_status on approvals (requested_by, status);
create trigger trg_approvals_updated_at before update on approvals
  for each row execute function set_updated_at();
