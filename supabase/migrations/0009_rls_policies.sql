-- ============================================================================
-- WSaler — Row Level Security policies
--
-- Model: any authenticated user can READ most operational tables (the app
-- does fine-grained UI gating via permissions), but WRITE access is gated
-- by auth_has_permission()/auth_has_role() mirroring the Laravel
-- `permission:` / `role:` middleware on each route. Admin bypasses via
-- auth_has_permission()'s built-in wildcard.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- profiles
-- ---------------------------------------------------------------------------
create policy "profiles: read own or admin/manager" on profiles
  for select to authenticated
  using (id = auth.uid() or auth_has_permission('view users'));

create policy "profiles: update own basic fields" on profiles
  for update to authenticated
  using (id = auth.uid() or auth_has_permission('edit users'));

create policy "profiles: admin can insert" on profiles
  for insert to authenticated
  with check (auth_has_permission('create users') or id = auth.uid());

create policy "profiles: admin can delete" on profiles
  for delete to authenticated
  using (auth_has_permission('delete users'));

-- ---------------------------------------------------------------------------
-- teams / territories / territory_user
-- ---------------------------------------------------------------------------
create policy "teams: read all authenticated" on teams for select to authenticated using (true);
create policy "teams: manage" on teams for all to authenticated
  using (auth_has_permission('teams.manage')) with check (auth_has_permission('teams.manage'));

create policy "territories: read all authenticated" on territories for select to authenticated using (true);
create policy "territories: manage" on territories for all to authenticated
  using (auth_has_permission('territories.manage')) with check (auth_has_permission('territories.manage'));

create policy "territory_user: read all authenticated" on territory_user for select to authenticated using (true);
create policy "territory_user: manage" on territory_user for all to authenticated
  using (auth_has_permission('territories.manage')) with check (auth_has_permission('territories.manage'));

-- ---------------------------------------------------------------------------
-- activity_logs (append-only audit trail)
-- ---------------------------------------------------------------------------
create policy "activity_logs: read own or admin" on activity_logs
  for select to authenticated
  using (user_id = auth.uid() or auth_is_admin());
create policy "activity_logs: insert own" on activity_logs
  for insert to authenticated
  with check (user_id = auth.uid() or user_id is null);

-- ---------------------------------------------------------------------------
-- approvals
-- ---------------------------------------------------------------------------
create policy "approvals: read requester or approver or reviewer" on approvals
  for select to authenticated
  using (requested_by = auth.uid() or approver_id = auth.uid() or auth_has_permission('approvals.review'));
create policy "approvals: insert own request" on approvals
  for insert to authenticated
  with check (requested_by = auth.uid());
create policy "approvals: decide as reviewer" on approvals
  for update to authenticated
  using (auth_has_permission('approvals.review') or requested_by = auth.uid());

-- ---------------------------------------------------------------------------
-- master data: units, warehouses, brands, categories, suppliers, customers
-- ---------------------------------------------------------------------------
create policy "units: read all authenticated" on units for select to authenticated using (true);
create policy "units: write with permission" on units for insert to authenticated with check (auth_has_permission('create products'));
create policy "units: update with permission" on units for update to authenticated using (auth_has_permission('edit products'));
create policy "units: delete with permission" on units for delete to authenticated using (auth_has_permission('delete products'));

create policy "warehouses: read all authenticated" on warehouses for select to authenticated using (true);
create policy "warehouses: insert with permission" on warehouses for insert to authenticated with check (auth_has_permission('create warehouses'));
create policy "warehouses: update with permission" on warehouses for update to authenticated using (auth_has_permission('edit warehouses'));
create policy "warehouses: delete with permission" on warehouses for delete to authenticated using (auth_has_permission('delete warehouses'));

create policy "brands: read all authenticated" on brands for select to authenticated using (true);
create policy "brands: insert with permission" on brands for insert to authenticated with check (auth_has_permission('create products'));
create policy "brands: update with permission" on brands for update to authenticated using (auth_has_permission('edit products'));
create policy "brands: delete with permission" on brands for delete to authenticated using (auth_has_permission('delete products'));

create policy "categories: read all authenticated" on categories for select to authenticated using (true);
create policy "categories: insert with permission" on categories for insert to authenticated with check (auth_has_permission('create products'));
create policy "categories: update with permission" on categories for update to authenticated using (auth_has_permission('edit products'));
create policy "categories: delete with permission" on categories for delete to authenticated using (auth_has_permission('delete products'));

create policy "suppliers: read with permission" on suppliers for select to authenticated using (auth_has_permission('view suppliers'));
create policy "suppliers: insert with permission" on suppliers for insert to authenticated with check (auth_has_permission('create suppliers'));
create policy "suppliers: update with permission" on suppliers for update to authenticated using (auth_has_permission('edit suppliers'));
create policy "suppliers: delete with permission" on suppliers for delete to authenticated using (auth_has_permission('delete suppliers'));

create policy "customers: read with permission" on customers for select to authenticated using (auth_has_permission('view customers'));
create policy "customers: insert with permission" on customers for insert to authenticated with check (auth_has_permission('create customers'));
create policy "customers: update with permission" on customers for update to authenticated using (auth_has_permission('edit customers'));
create policy "customers: delete with permission" on customers for delete to authenticated using (auth_has_permission('delete customers'));

create policy "customer_assignments: read own or manage" on customer_assignments
  for select to authenticated
  using (salesperson_user_id = auth.uid() or auth_has_permission('assignments.view') or auth_has_permission('assignments.manage'));
create policy "customer_assignments: manage" on customer_assignments
  for all to authenticated
  using (auth_has_permission('assignments.manage')) with check (auth_has_permission('assignments.manage'));

-- ---------------------------------------------------------------------------
-- products & catalog
-- ---------------------------------------------------------------------------
create policy "products: read with permission" on products for select to authenticated using (auth_has_permission('view products'));
create policy "products: insert with permission" on products for insert to authenticated with check (auth_has_permission('create products'));
create policy "products: update with permission" on products for update to authenticated using (auth_has_permission('edit products'));
create policy "products: delete with permission" on products for delete to authenticated using (auth_has_permission('delete products'));

create policy "variations: read with permission" on product_variations for select to authenticated using (auth_has_permission('view products'));
create policy "variations: insert with permission" on product_variations for insert to authenticated with check (auth_has_permission('create products'));
create policy "variations: update with permission" on product_variations for update to authenticated using (auth_has_permission('edit products'));
create policy "variations: delete with permission" on product_variations for delete to authenticated using (auth_has_permission('delete products'));

create policy "price_breaks: read with permission" on product_price_breaks for select to authenticated using (auth_has_permission('view products'));
create policy "price_breaks: manage" on product_price_breaks for all to authenticated
  using (auth_has_permission('edit products')) with check (auth_has_permission('edit products'));

create policy "price_lists: read with permission" on price_lists for select to authenticated using (auth_has_permission('view products'));
create policy "price_lists: manage" on price_lists for all to authenticated
  using (auth_has_permission('edit products')) with check (auth_has_permission('edit products'));

-- ---------------------------------------------------------------------------
-- batches / stock movements
-- ---------------------------------------------------------------------------
create policy "batches: read with permission" on batches for select to authenticated using (auth_has_permission('view batches'));
create policy "batches: insert with permission" on batches for insert to authenticated with check (auth_has_permission('create batches'));
create policy "batches: update with permission" on batches for update to authenticated using (auth_has_permission('edit batches'));
create policy "batches: delete with permission" on batches for delete to authenticated using (auth_has_permission('delete batches'));

create policy "stock_movements: read with permission" on stock_movements for select to authenticated using (auth_has_permission('view inventory'));
-- stock_movements are only ever written by SECURITY DEFINER functions (create_sale, void_sale,
-- receive_purchase, transfer_stock, adjust_stock) — no direct insert/update/delete policy for
-- ordinary authenticated role. This forces all writes through the vetted business-logic functions.

-- ---------------------------------------------------------------------------
-- sales / POS
-- ---------------------------------------------------------------------------
create policy "sales: read with permission" on sales for select to authenticated using (auth_has_permission('view reports') or user_id = auth.uid());
-- sales/sale_items/sale_payments are written exclusively via create_sale()/void_sale() (SECURITY
-- DEFINER). No direct insert policy — matches Laravel's SaleService being the only write path.

create policy "sale_items: read with permission" on sale_items for select to authenticated
  using (exists (select 1 from sales s where s.id = sale_items.sale_id and (auth_has_permission('view reports') or s.user_id = auth.uid())));

create policy "sale_payments: read with permission" on sale_payments for select to authenticated
  using (exists (select 1 from sales s where s.id = sale_payments.sale_id and (auth_has_permission('view reports') or s.user_id = auth.uid())));

create policy "draft_orders: read own" on draft_orders for select to authenticated
  using (user_id = auth.uid() or auth_has_permission('access pos'));
create policy "draft_orders: manage own" on draft_orders for all to authenticated
  using (user_id = auth.uid()) with check (user_id = auth.uid());

create policy "refunds: read with permission" on refunds for select to authenticated using (auth_has_permission('view reports'));
create policy "refunds: insert with permission" on refunds for insert to authenticated with check (auth_has_permission('process sales returns'));
create policy "refund_items: read with permission" on refund_items for select to authenticated
  using (exists (select 1 from refunds r where r.id = refund_items.refund_id and auth_has_permission('view reports')));
create policy "refund_items: insert with permission" on refund_items for insert to authenticated
  with check (auth_has_permission('process sales returns'));

-- ---------------------------------------------------------------------------
-- purchasing & inventory ops
-- ---------------------------------------------------------------------------
create policy "purchase_orders: read with permission" on purchase_orders for select to authenticated using (auth_has_permission('view inventory'));
create policy "purchase_orders: insert with permission" on purchase_orders for insert to authenticated with check (auth_has_permission('create invoices'));
create policy "purchase_orders: update with permission" on purchase_orders for update to authenticated using (auth_has_permission('edit invoices'));
create policy "purchase_orders: delete with permission" on purchase_orders for delete to authenticated using (auth_has_permission('cancel sales'));

create policy "po_items: read with permission" on purchase_order_items for select to authenticated
  using (exists (select 1 from purchase_orders po where po.id = purchase_order_items.purchase_order_id and auth_has_permission('view inventory')));
create policy "po_items: manage with permission" on purchase_order_items for all to authenticated
  using (auth_has_permission('edit invoices')) with check (auth_has_permission('edit invoices'));

create policy "purchase_receipts: read with permission" on purchase_receipts for select to authenticated using (auth_has_permission('view inventory'));
create policy "purchase_receipt_items: read with permission" on purchase_receipt_items for select to authenticated
  using (exists (select 1 from purchase_receipts pr where pr.id = purchase_receipt_items.purchase_receipt_id and auth_has_permission('view inventory')));
-- purchase_receipts/items are written via receive_purchase() (SECURITY DEFINER) to keep batch
-- creation + stock_movements consistent, matching the Laravel InventoryController flow.

create policy "stock_transfers: read with permission" on stock_transfers for select to authenticated using (auth_has_permission('view inventory'));
create policy "stock_transfer_items: read with permission" on stock_transfer_items for select to authenticated
  using (exists (select 1 from stock_transfers st where st.id = stock_transfer_items.stock_transfer_id and auth_has_permission('view inventory')));

create policy "stock_adjustments: read with permission" on stock_adjustments for select to authenticated using (auth_has_permission('view inventory'));
create policy "stock_adjustment_items: read with permission" on stock_adjustment_items for select to authenticated
  using (exists (select 1 from stock_adjustments sa where sa.id = stock_adjustment_items.stock_adjustment_id and auth_has_permission('view inventory')));

create policy "supplier_payments: read with permission" on supplier_payments for select to authenticated using (auth_has_permission('view inventory'));
create policy "supplier_payments: insert with permission" on supplier_payments for insert to authenticated with check (auth_has_permission('create invoices'));
create policy "supplier_payments: update with permission" on supplier_payments for update to authenticated using (auth_has_permission('edit invoices'));
create policy "supplier_payments: delete with permission" on supplier_payments for delete to authenticated using (auth_has_permission('cancel sales'));

create policy "expense_categories: read all authenticated" on expense_categories for select to authenticated using (true);
create policy "expense_categories: manage" on expense_categories for all to authenticated
  using (auth_has_permission('manage expenses')) with check (auth_has_permission('manage expenses'));

create policy "expenses: read with permission" on expenses for select to authenticated using (auth_has_permission('view financial reports') or user_id = auth.uid());
create policy "expenses: manage with permission" on expenses for all to authenticated
  using (auth_has_permission('manage expenses')) with check (auth_has_permission('manage expenses'));

-- ---------------------------------------------------------------------------
-- sales performance: target templates, targets, achievements
-- ---------------------------------------------------------------------------
create policy "target_templates: read with permission" on target_templates for select to authenticated using (auth_has_permission('target_templates.view'));
create policy "target_templates: manage" on target_templates for all to authenticated
  using (auth_has_permission('target_templates.manage')) with check (auth_has_permission('target_templates.manage'));

create policy "target_template_lines: read with permission" on target_template_lines for select to authenticated
  using (auth_has_permission('target_templates.view'));
create policy "target_template_lines: manage" on target_template_lines for all to authenticated
  using (auth_has_permission('target_templates.manage')) with check (auth_has_permission('target_templates.manage'));

create policy "sales_targets: read own or manage" on sales_targets for select to authenticated
  using (salesperson_user_id = auth.uid() or auth_has_permission('targets.view') or auth_has_permission('targets.manage'));
create policy "sales_targets: manage" on sales_targets for all to authenticated
  using (auth_has_permission('targets.manage')) with check (auth_has_permission('targets.manage'));

create policy "sales_target_lines: read own or manage" on sales_target_lines for select to authenticated
  using (exists (
    select 1 from sales_targets st where st.id = sales_target_lines.sales_target_id
    and (st.salesperson_user_id = auth.uid() or auth_has_permission('targets.view') or auth_has_permission('targets.manage'))
  ));
create policy "sales_target_lines: manage" on sales_target_lines for all to authenticated
  using (auth_has_permission('targets.manage')) with check (auth_has_permission('targets.manage'));

create policy "achievements: read own or manage" on sales_target_achievements for select to authenticated
  using (exists (
    select 1 from sales_target_lines l
    join sales_targets st on st.id = l.sales_target_id
    where l.id = sales_target_achievements.sales_target_line_id
    and (st.salesperson_user_id = auth.uid() or auth_has_permission('targets.view') or auth_has_permission('targets.manage'))
  ));
create policy "achievements: manage" on sales_target_achievements for all to authenticated
  using (auth_has_permission('targets.manage')) with check (auth_has_permission('targets.manage'));
