-- ============================================================================
-- WSaler — Sales performance: target templates, sales targets, achievements
-- ============================================================================

create table if not exists target_templates (
  id bigint generated always as identity primary key,
  name text not null,
  period_type target_period not null,
  description text,
  is_active boolean not null default true,
  created_by uuid not null references profiles (id) on delete restrict,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz
);
create index if not exists idx_target_templates_active_period on target_templates (is_active, period_type);
create trigger trg_target_templates_updated_at before update on target_templates
  for each row execute function set_updated_at();

create table if not exists target_template_lines (
  id bigint generated always as identity primary key,
  target_template_id bigint not null references target_templates (id) on delete cascade,
  metric target_metric not null,
  default_value numeric(18,4) not null,
  order_index integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (target_template_id, metric)
);

create table if not exists sales_targets (
  id bigint generated always as identity primary key,
  salesperson_user_id uuid not null references profiles (id) on delete cascade,
  period_type target_period not null,
  period_start date not null,
  period_end date not null,
  target_template_id bigint references target_templates (id) on delete set null,
  name text not null,
  status target_status not null default 'draft',
  created_by uuid not null references profiles (id) on delete restrict,
  approved_by uuid references profiles (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz,
  unique (salesperson_user_id, period_type, period_start)
);
create index if not exists idx_sales_targets_status_end on sales_targets (status, period_end);
create trigger trg_sales_targets_updated_at before update on sales_targets
  for each row execute function set_updated_at();

create table if not exists sales_target_lines (
  id bigint generated always as identity primary key,
  sales_target_id bigint not null references sales_targets (id) on delete cascade,
  metric target_metric not null,
  target_value numeric(18,4) not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (sales_target_id, metric)
);

create table if not exists sales_target_achievements (
  id bigint generated always as identity primary key,
  sales_target_line_id bigint not null references sales_target_lines (id) on delete cascade,
  snapshot_date date not null,
  achieved_value numeric(18,4) not null default 0,
  achievement_pct numeric(8,4) not null default 0,
  computed_at timestamptz not null default now(),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (sales_target_line_id, snapshot_date)
);
create index if not exists idx_achievements_snapshot_date on sales_target_achievements (snapshot_date);

alter table target_templates enable row level security;
alter table target_template_lines enable row level security;
alter table sales_targets enable row level security;
alter table sales_target_lines enable row level security;
alter table sales_target_achievements enable row level security;
alter table territories enable row level security;
alter table territory_user enable row level security;
alter table teams enable row level security;
alter table activity_logs enable row level security;
alter table approvals enable row level security;
alter table profiles enable row level security;
