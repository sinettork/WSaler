-- ============================================================================
-- WSaler — Extensions & Enum Types
-- ============================================================================
create extension if not exists "pgcrypto";
create extension if not exists "pg_trgm"; -- trigram indexes for fast ILIKE search

-- ---------------------------------------------------------------------------
-- Enums (mirrors app/Enums/*.php from the Laravel version)
-- ---------------------------------------------------------------------------
do $$ begin
  create type user_role as enum ('admin','manager','cashier','warehouse','purchasing','delivery','salesperson','accountant');
exception when duplicate_object then null; end $$;

do $$ begin
  create type employment_status as enum ('active','inactive','on_leave','terminated');
exception when duplicate_object then null; end $$;

do $$ begin
  create type target_period as enum ('daily','weekly','monthly','quarterly','annual');
exception when duplicate_object then null; end $$;

do $$ begin
  create type target_metric as enum ('sales_amount','invoice_count','customer_count','quantity','gross_profit','collection_amount','new_customer_count');
exception when duplicate_object then null; end $$;

do $$ begin
  create type target_status as enum ('draft','active','achieved','expired','cancelled');
exception when duplicate_object then null; end $$;

do $$ begin
  create type assignment_status as enum ('pending','active','expired','revoked');
exception when duplicate_object then null; end $$;

do $$ begin
  create type approval_status as enum ('pending','approved','rejected','cancelled');
exception when duplicate_object then null; end $$;

do $$ begin
  create type sale_status as enum ('draft','completed','voided','refunded');
exception when duplicate_object then null; end $$;

do $$ begin
  create type payment_method as enum ('cash','credit','bank_transfer','e_wallet','card','check','mobile_money','other');
exception when duplicate_object then null; end $$;

do $$ begin
  create type product_status as enum ('active','inactive','discontinued');
exception when duplicate_object then null; end $$;

do $$ begin
  create type batch_status as enum ('active','expired','depleted','disposed');
exception when duplicate_object then null; end $$;

do $$ begin
  create type customer_type as enum ('retail','wholesale','distributor');
exception when duplicate_object then null; end $$;

do $$ begin
  create type stock_movement_type as enum ('purchase','sale','sale_void','transfer_in','transfer_out','adjustment_increase','adjustment_decrease','refund','disposal');
exception when duplicate_object then null; end $$;

do $$ begin
  create type document_status as enum ('draft','pending','approved','completed','cancelled','rejected');
exception when duplicate_object then null; end $$;

-- ---------------------------------------------------------------------------
-- updated_at trigger helper
-- ---------------------------------------------------------------------------
create or replace function set_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;
