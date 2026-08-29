-- ============================================================================
-- WSaler — Seed data: units, warehouses, expense categories.
-- Ports UnitSeeder.php / WarehouseSeeder.php verbatim; expense categories
-- are a small reasonable default set (no ExpenseCategorySeeder existed in
-- the legacy app to port from).
-- ============================================================================

insert into units (name, short_code, base, conversion_factor_to_base) values
  ('Piece', 'pcs', true, 1),
  ('Box', 'box', false, 6),
  ('Carton', 'ctn', false, 24),
  ('Kilogram', 'kg', true, 1),
  ('Liter', 'l', true, 1),
  ('Pack', 'pack', false, 12)
on conflict (short_code) do nothing;

insert into warehouses (name, code, address, phone, is_default, is_active) values
  ('Main Warehouse', 'WH-001', '123 Main St, Industrial Area', '1234567890', true, true),
  ('Branch Warehouse', 'WH-002', '456 Branch Rd, Downtown', '0987654321', false, true),
  ('Returns Warehouse', 'WH-003', '789 Returns Ave, Outskirts', null, false, true)
on conflict (code) do nothing;

insert into expense_categories (name, code, description, is_active) values
  ('Rent', 'RENT', 'Warehouse/office rent', true),
  ('Utilities', 'UTIL', 'Electricity, water, internet', true),
  ('Salaries', 'SAL', 'Staff salaries and wages', true),
  ('Transportation', 'TRANS', 'Fuel, delivery, logistics', true),
  ('Office Supplies', 'OFFSUP', 'Stationery and consumables', true),
  ('Maintenance', 'MAINT', 'Equipment and facility maintenance', true),
  ('Marketing', 'MKT', 'Advertising and promotions', true),
  ('Other', 'OTHER', 'Miscellaneous expenses', true)
on conflict (code) do nothing;
