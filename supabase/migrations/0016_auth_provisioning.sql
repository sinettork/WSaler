-- ============================================================================
-- WSaler — auth provisioning: auto-create a `profiles` row whenever a new
-- user is created in Supabase Auth (`auth.users`), mirroring the Laravel
-- AuthController::register() behaviour, which always created the app-side
-- `users` row + assigned the Cashier role at signup time.
--
-- Without this trigger, a brand-new supabase.auth.signUp() account would
-- have an auth.users row but no `profiles` row, and would fail every
-- permission check (auth_has_permission()/auth_has_role() query `profiles`
-- keyed by auth.uid()).
-- ============================================================================

create or replace function handle_new_user()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  insert into public.profiles (id, name, email, role)
  values (
    new.id,
    coalesce(new.raw_user_meta_data ->> 'name', split_part(new.email, '@', 1)),
    new.email,
    'cashier' -- matches legacy AuthController::register(): new signups default to Cashier
  )
  on conflict (id) do nothing;

  return new;
end;
$$;

drop trigger if exists trg_auth_user_created on auth.users;
create trigger trg_auth_user_created
  after insert on auth.users
  for each row execute function handle_new_user();

-- ---------------------------------------------------------------------------
-- Keep profiles.email in sync if a user changes their email via Supabase Auth
-- (e.g. email change confirmation). Not part of legacy parity strictly, but
-- prevents profiles.email from silently drifting out of sync with auth.users.
-- ---------------------------------------------------------------------------
create or replace function handle_user_email_updated()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  if new.email is distinct from old.email then
    update public.profiles set email = new.email where id = new.id;
  end if;
  return new;
end;
$$;

drop trigger if exists trg_auth_user_email_updated on auth.users;
create trigger trg_auth_user_email_updated
  after update on auth.users
  for each row execute function handle_user_email_updated();
