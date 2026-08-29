-- ============================================================================
-- WSaler — Cambodia address cascader (provinces → districts → communes → villages)
-- Public reference data, read-only for the app.
-- ============================================================================

create table if not exists provinces (
  id bigint generated always as identity primary key,
  code text not null unique,
  name_en text not null,
  name_km text not null,
  type text not null default 'province' check (type in ('province', 'municipality')),
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_provinces_type_sort on provinces (type, sort_order);
create index if not exists idx_provinces_name_en on provinces (name_en);
create index if not exists idx_provinces_name_km on provinces (name_km);

create table if not exists districts (
  id bigint generated always as identity primary key,
  code text not null,
  province_id bigint not null references provinces (id) on delete cascade,
  name_en text not null,
  name_km text not null,
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (province_id, code)
);
create index if not exists idx_districts_name_en on districts (name_en);
create index if not exists idx_districts_name_km on districts (name_km);

create table if not exists communes (
  id bigint generated always as identity primary key,
  code text not null,
  district_id bigint not null references districts (id) on delete cascade,
  name_en text not null,
  name_km text not null,
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (district_id, code)
);
create index if not exists idx_communes_name_en on communes (name_en);
create index if not exists idx_communes_name_km on communes (name_km);

create table if not exists villages (
  id bigint generated always as identity primary key,
  code text not null,
  commune_id bigint not null references communes (id) on delete cascade,
  name_en text not null,
  name_km text not null,
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (commune_id, code)
);
create index if not exists idx_villages_name_en on villages (name_en);
create index if not exists idx_villages_name_km on villages (name_km);

alter table provinces enable row level security;
alter table districts enable row level security;
alter table communes enable row level security;
alter table villages enable row level security;

create policy "addresses readable by authenticated" on provinces for select to authenticated using (true);
create policy "addresses readable by authenticated" on districts for select to authenticated using (true);
create policy "addresses readable by authenticated" on communes for select to authenticated using (true);
create policy "addresses readable by authenticated" on villages for select to authenticated using (true);
