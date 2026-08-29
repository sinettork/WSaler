# Wsaler — Implementation Roadmap (Phase 2 onward)

> **Status of Phase 1 (Foundation MVP):** ✅ Shipped
> Auth, RBAC, Master Data (Users/Categories/Brands/Suppliers/Customers/Units/Warehouses), Products with multi-unit & variations, Batches with FEFO, StockMovement model. Vue 3 SPA shell + Pinia stores + Bootstrap 5 UI for the above.

This document defines the **next 6 phases** that turn the foundation into a complete Wholesale POS & Inventory system. Each phase is a vertical slice (backend + frontend + tests) that delivers a demoable increment.

---

## Phase Index

| Phase | Name | Primary User | Core Outcome | Estimated Slices |
|------:|---|---|---|---|
| 2 | Sales / POS | Cashier | Process a sale, deduct stock via FEFO, take payment, print invoice | 3 |
| 3 | Purchase Orders & Receiving | Purchasing | Order from supplier → receive → auto-create batches | 2 |
| 4 | Inventory Operations | Warehouse | Adjustments, inter-warehouse transfers, stock counts | 2 |
| 5 | Reporting & Dashboard | Manager / Owner | Live KPIs, sales reports, low-stock alerts | 2 |
| 6 | Customer Pricing & Credit | Manager / Cashier | Price lists, credit limits, statements, collections | 2 |
| 7 | Deliveries, Returns, Notifications | Delivery / Cashier | Last-mile fulfilment, returns, real-time alerts | 3 |

After Phase 7 the system is feature-complete for SMB wholesale. A separate **Phase 8 (Polish & Scale)** covers hardening, performance, mobile, and integrations — listed at the end as out-of-scope follow-ups.

---

## Phase 2 — Sales / POS (Cashier Workflow)

**Goal:** A cashier can ring up a sale, the system deducts stock via FEFO, records payment, and prints/saves an invoice — atomically.

**Why first:** This is the core revenue flow. Until POS works, the system is just a catalog. FEFO logic exists already; this phase wires it to a sale.

### Slice 2.1 — Sales data model & API (CRUD + checkout)
**Complexity: complex** — multi-table transaction with stock deduction + concurrency safety.

**Backend**
- `database/migrations/*_create_sales_table.php`
  - `invoice_number` (unique), `customer_id` (nullable for walk-in), `warehouse_id`, `user_id` (cashier), `subtotal`, `discount`, `tax`, `total`, `paid`, `change`, `status` (enum: draft, completed, voided, refunded), `notes`, `sold_at`
- `database/migrations/*_create_sale_items_table.php`
  - `sale_id`, `product_id`, `variation_id` (nullable), `quantity`, `unit_id`, `unit_price`, `discount`, `line_total`
- `database/migrations/*_create_sale_payments_table.php`
  - `sale_id`, `method` (cash, credit, bank_transfer, e_wallet), `amount`, `reference`, `paid_at`
- `database/migrations/*_create_price_lists_table.php` (forward-declared, used in 2.3)
  - `name`, `customer_id` (nullable = default), `product_id`, `unit_id`, `price`, `min_qty`, `valid_from`, `valid_to`
- Models: `Sale`, `SaleItem`, `SalePayment`, `PriceList`
- `app/Services/SaleService.php` — **transactional** checkout method:
  - Wraps in `DB::transaction` with row-level lock on batches (`lockForUpdate`)
  - Calls `FefoBatchSelector` to allocate quantity across batches
  - Creates `SaleItem` rows
  - Inserts `StockMovement` rows (type = `sale`, `reference_type = Sale`, `reference_id = sale->id`)
  - Creates `SalePayment` rows
  - Returns sale with eager-loaded items/payments/movements
- `app/Http/Controllers/Api/SaleController.php`
  - `index` (filter by date, cashier, customer, status)
  - `show` (full invoice detail)
  - `store` → calls `SaleService::checkout`
  - `void` (admin/manager) — reverses stock movements
- `app/Http/Requests/StoreSaleRequest.php` — validates items array, payment totals
- `app/Http/Resources/SaleResource.php`, `SaleItemResource.php`
- `routes/api.php` — sales routes with role middleware (`cashier,manager,admin` for write)
- `tests/Feature/SaleTest.php`:
  - Happy path: 3-batch product, 150 units, picks earliest first
  - Insufficient stock → 422
  - Multi-payment split (cash + credit) → both rows created, totals match
  - Void reverses stock movements
  - Race: 2 concurrent sales on same stock — one fails with 422 (uses lockForUpdate)
- `tests/Unit/Services/SaleServiceTest.php`

**Acceptance**
- `POST /api/sales` with 3 items deducts correct batches via FEFO
- Stock movements are linked back to the sale via `reference`
- Tests pass with `-race` flag

### Slice 2.2 — Cashier POS UI
**Complexity: complex** — real-time product lookup, cart, FEFO-aware stock display, payment modal.

**Frontend**
- `resources/js/stores/sales.js` — Pinia: cart, current sale, totals, payments, submit
- `resources/js/pages/pos/POS.vue` — three-pane layout:
  - **Left:** category tabs → product grid (loads via `/api/products/lookup`)
  - **Center:** cart line items with qty stepper, unit selector, live subtotal
  - **Right:** customer picker (searchable), payment panel (method buttons + amount), grand total, "Charge" button
- `resources/js/components/pos/ProductCard.vue` — shows available stock + price
- `resources/js/components/pos/CartLine.vue` — qty controls, removes item
- `resources/js/components/pos/PaymentPanel.vue` — multi-method split, change calculation
- `resources/js/components/pos/CustomerPicker.vue` — debounced search
- `resources/js/pages/sales/SaleDetail.vue` — printable invoice
- `resources/js/pages/sales/SaleList.vue` — list with date filter + status badges
- Router: `/pos` (cashier-only guard), `/sales`, `/sales/:id`

**Acceptance**
- Cashier logs in → `/pos` loads product grid within 1s on warm cache
- Adding 150 units of a 3-batch product shows correct FEFO allocation in the API response (visible in dev tools)
- Charging splits payment across cash + credit; sale created with status `completed`
- `/sales/:id` shows printable invoice with items, payments, totals

### Slice 2.3 — Returns & Voids
**Complexity: simple** — extends existing sale flow.

**Backend**
- `app/Http/Controllers/Api/SaleReturnController.php`
- `POST /api/sales/{sale}/returns` — partial or full, restocks returned batches, creates opposite-direction `StockMovement`
- `POST /api/sales/{sale}/void` — manager-only, reverses all movements
- Migration to add `parent_sale_id` to `sales` for refund-of-refund chains
- `tests/Feature/SaleReturnTest.php`

**Frontend**
- `resources/js/pages/sales/SaleReturn.vue` — modal on `SaleDetail` page
- "Void" button (manager-only) with confirmation

**Acceptance**
- Returning 10 of 50 units creates +10 stock movement back into the same batch
- Void requires manager role; cashier gets 403
- Stock movements are auditable in batch history

---

## Phase 3 — Purchase Orders & Receiving

**Goal:** A purchasing officer drafts an order to a supplier, sends it, then receives goods — which auto-creates batches with FEFO-ready expiry dates.

**Why second:** Closes the inventory loop. Without PO + receiving, batches can only be created manually — no traceability back to supplier price/cost.

### Slice 3.1 — Purchase orders data model & API
**Complexity: simple** — CRUD with status state machine.

**Backend**
- `database/migrations/*_create_purchase_orders_table.php`
  - `po_number`, `supplier_id`, `warehouse_id`, `user_id`, `status` (draft, sent, partial, received, cancelled), `subtotal`, `tax`, `total`, `expected_at`, `received_at`, `notes`
- `database/migrations/*_create_purchase_order_items_table.php`
  - `po_id`, `product_id`, `variation_id` (nullable), `quantity`, `unit_id`, `unit_cost`, `received_quantity` (default 0), `line_total`
- Models: `PurchaseOrder`, `PurchaseOrderItem`
- `app/Http/Controllers/Api/PurchaseOrderController.php` — index, show, store, update, destroy (only if draft), cancel
- `app/Http/Requests/StorePurchaseOrderRequest.php`
- `app/Http/Resources/PurchaseOrderResource.php`
- `tests/Feature/PurchaseOrderTest.php`

**Acceptance**
- POST creates PO with `draft` status
- Cannot delete PO once status ≠ draft (422)
- Filters by supplier, status, date

### Slice 3.2 — Receiving → auto-create batches
**Complexity: complex** — multi-table transaction, partial receipts, expiry capture.

**Backend**
- `app/Services/ReceivingService.php` — `receivePurchaseOrder(PurchaseOrder, array $items)`
  - For each item line: validate `received_quantity ≤ remaining`
  - Create or update `Batch` (by batch_number from PO)
  - Increment `quantity` and `remaining_quantity`
  - Insert `StockMovement` (type = `purchase`, reference = PO)
  - Update `purchase_order_items.received_quantity`
  - Recompute PO status: `partial` if any line short, `received` if all complete
- `app/Http/Controllers/Api/PurchaseOrderController.php` adds `receive` action: `POST /api/purchase-orders/{po}/receive`
- Capture `manufacture_date`, `expiry_date`, `purchase_cost` per received line
- `tests/Feature/ReceivingTest.php`:
  - Full receipt → PO status `received`, batches created
  - Partial receipt → status `partial`, can be received again
  - Over-receipt rejected (422)

**Frontend**
- `resources/js/stores/purchaseOrders.js`
- `resources/js/pages/purchasing/PurchaseOrderList.vue`
- `resources/js/pages/purchasing/PurchaseOrderForm.vue` — line editor with product picker
- `resources/js/pages/purchasing/PurchaseOrderReceive.vue` — receive form with batch number, expiry date, qty columns
- Router: `/purchasing`, `/purchasing/new`, `/purchasing/:id`, `/purchasing/:id/receive`

**Acceptance**
- Receiving 100/200 units creates batch with `remaining_quantity=100` and `StockMovement` of type `purchase`
- A second receive on same PO for remaining 100 → PO status flips to `received`
- Receiving page validates expiry_date > manufacture_date

---

## Phase 4 — Inventory Operations

**Goal:** Warehouse staff can correct stock, transfer between warehouses, and run periodic stock counts — all producing auditable `StockMovement` records.

### Slice 4.1 — Stock adjustments
**Complexity: simple** — single-row transactions.

**Backend**
- `app/Http/Controllers/Api/StockAdjustmentController.php`
- `POST /api/stock-adjustments` — body: `{batch_id, type (damage, recount, expiry, other), quantity_change, reason}`
- Creates `StockMovement` with `type = adjustment`
- Validates `quantity_change` doesn't drive `remaining_quantity` negative
- `GET /api/stock-adjustments` — list with filters
- `tests/Feature/StockAdjustmentTest.php`

**Frontend**
- `resources/js/pages/warehouse/Adjustments.vue` — list + new
- Quick action on batch detail page: "Adjust"

### Slice 4.2 — Inter-warehouse transfers
**Complexity: complex** — paired movements, atomicity, transfer state.

**Backend**
- `database/migrations/*_create_stock_transfers_table.php`
  - `transfer_number`, `from_warehouse_id`, `to_warehouse_id`, `status` (draft, in_transit, received, cancelled), `shipped_at`, `received_at`, `user_id`
- `database/migrations/*_create_stock_transfer_items_table.php`
  - `transfer_id`, `batch_id`, `quantity`
- `app/Services/StockTransferService.php`:
  - `ship` — decrements source `batch.remaining_quantity`, creates `StockMovement` (type = `transfer_out`)
  - `receive` — increments destination batch (find or create by `batch_number+warehouse_id`), creates `StockMovement` (type = `transfer_in`)
- Controller: `StockTransferController` (index, store, ship, receive, cancel)
- `tests/Feature/StockTransferTest.php`

**Frontend**
- `resources/js/pages/warehouse/Transfers.vue`
- `resources/js/pages/warehouse/TransferForm.vue`
- Receive mode: pre-filled with shipped qty, allows short-receive

**Acceptance**
- Transfer A→B of 50 units: A's stock decrements on ship, B's stock increments on receive
- Source and destination stock movements reference the same `StockTransfer`
- Cannot receive more than shipped

---

## Phase 5 — Reporting & Dashboard

**Goal:** Manager sees live KPIs (today's sales, low-stock count, expiring batches, top customers) without running ad-hoc queries.

### Slice 5.1 — Reports API
**Complexity: simple** — read-only aggregations.

**Backend**
- `app/Http/Controllers/Api/ReportController.php`
  - `GET /api/reports/sales-summary?from=&to=&group_by=day|week|month` — totals, count, avg ticket
  - `GET /api/reports/top-products?from=&to=&limit=10`
  - `GET /api/reports/low-stock` — products where total remaining < threshold
  - `GET /api/reports/expiring-soon?days=30` — batches expiring within window
  - `GET /api/reports/inventory-valuation` — qty × latest cost per batch
  - `GET /api/reports/profit-loss?from=&to=` — revenue − cost-of-goods-sold
- All queries scoped by warehouse where applicable
- Indexes added on `sales(sold_at)`, `stock_movements(occurred_at)` if missing
- `tests/Feature/ReportTest.php` — fixtures, asserts aggregate math

**Acceptance**
- Sales summary for a known date range returns expected totals
- Low-stock query returns products below their configured threshold
- Each endpoint < 300ms with 10k sales rows (indexed)

### Slice 5.2 — Dashboard UI
**Complexity: simple** — cards + charts.

**Frontend**
- `resources/js/pages/Dashboard.vue` — replace placeholder:
  - KPI cards: today's sales, today's orders, low-stock count, expiring batches
  - Chart: 7-day sales trend (use `chart.js` or simple SVG bars — no new heavy deps)
  - Lists: top 5 products, top 5 customers, alerts panel
- Pinia store `reports.js` with cached fetches (5-min TTL)
- Role-aware: cashier sees own sales; manager/admin sees global

**Acceptance**
- Dashboard loads < 1s with charts visible
- Cards update after a new sale is made (refetch on focus)
- Manager sees aggregate; cashier sees only their own transactions

---

## Phase 6 — Customer Pricing & Credit

**Goal:** Wholesale customer gets their negotiated price; credit customers have a limit; manager can review outstanding balances.

### Slice 6.1 — Customer-specific pricing
**Complexity: simple** — extends SaleService.

**Backend**
- `PriceList` model already exists from Phase 2 — add admin CRUD
- `app/Http/Controllers/Api/PriceListController.php`
- `GET /api/price-lists/resolve?customer_id=&product_id=&quantity=&unit_id=` → returns resolved price with audit trail (which price list matched)
- `SaleService::checkout` uses `PriceListResolver` if a customer is set; falls back to product default
- `tests/Feature/PriceListTest.php`:
  - Customer A has $8 price; walk-in gets $10 default
  - Volume break (min_qty=10 → $7.50) applied correctly

**Frontend**
- `resources/js/pages/master/PriceLists.vue`
- Customer detail page shows assigned price lists with effective dates
- POS: shows resolved price for selected customer

### Slice 6.2 — Credit management
**Complexity: complex** — credit limits, aging buckets, partial payments.

**Backend**
- Migration: add `credit_limit`, `current_balance`, `payment_terms_days` to `customers`
- `app/Services/CreditService.php`:
  - `getAvailableCredit(customer)` = `credit_limit − current_balance`
  - `recordPayment(customer, amount)` decrements balance, logs payment
  - `getAgingReport()` — buckets 0-30, 31-60, 61-90, 90+ days
- `POST /api/customers/{customer}/payments` — accepts payment, allocates to oldest invoices first
- Block sale creation in `SaleService` if `payment.method = credit` and `amount > available_credit` (returns 422 with clear message)
- `tests/Feature/CreditTest.php`

**Frontend**
- `resources/js/pages/customers/CustomerDetail.vue` — shows credit utilization, recent payments, outstanding invoices
- `resources/js/pages/customers/PaymentCollection.vue` — manager view of aging report, record payment form

**Acceptance**
- Customer with $1000 limit, $400 balance → $700 credit sale allowed, $800 rejected
- Recording a $200 payment allocates to oldest unpaid sale first
- Aging report matches seeded fixtures

---

## Phase 7 — Deliveries, Returns, Notifications

**Goal:** Goods reach the customer; alerts reach the right person; returns are processed cleanly.

### Slice 7.1 — Deliveries
**Complexity: simple** — extends Sale.

**Backend**
- `database/migrations/*_create_deliveries_table.php`
  - `delivery_number`, `sale_id`, `driver_id`, `status` (pending, assigned, picked_up, in_transit, delivered, failed), `address`, `scheduled_at`, `delivered_at`, `notes`, `proof_image` (nullable)
- `app/Http/Controllers/Api/DeliveryController.php` — CRUD + status transitions
- Driver assignment uses `users WHERE role = delivery`
- `tests/Feature/DeliveryTest.php`

**Frontend**
- `resources/js/pages/delivery/DeliveryList.vue` (manager)
- `resources/js/pages/delivery/MyDeliveries.vue` (driver view)
- `resources/js/pages/delivery/DeliveryDetail.vue`

### Slice 7.2 — Supplier returns
**Complexity: simple** — inverse of receiving.

**Backend**
- `app/Services/SupplierReturnService.php` — decrement batch quantity, create `StockMovement` (type = `supplier_return`)
- `POST /api/purchase-orders/{po}/returns`
- `tests/Feature/SupplierReturnTest.php`

**Frontend**
- Reuse `PurchaseOrderReceive.vue` with "Return" mode

### Slice 7.3 — Notifications
**Complexity: complex** — event-driven, multiple channels.

**Backend**
- `app/Notifications/*` — `LowStockAlert`, `ExpiringBatchAlert`, `LargeSaleAlert`
- Listeners on Eloquent events (e.g., `BatchSaved`, `SaleCreated`)
- Channels: database (in-app bell icon) + email (queued via Laravel notifications)
- Daily digest job via scheduler: expiring batches in 30/60/90 days
- `tests/Feature/NotificationTest.php` (use `Notification::fake()`)

**Frontend**
- `resources/js/components/NotificationBell.vue` — polls `/api/notifications`
- `resources/js/stores/notifications.js`
- Toast on low-stock events when cashier is selling

**Acceptance**
- Creating a sale that drives stock below threshold → `LowStockAlert` dispatched
- Daily scheduled job dispatches expiring-batch notifications
- Bell icon unread count updates without page reload

---

## Phase 8 — Polish & Scale (Out of Scope for Above)

Deferred items explicitly not in phases 2–7. Each is a candidate for its own ferment later.

- **Performance:** Eager-loading audit, N+1 detection (`laravel-debugbar`), DB index review, query caching for reports
- **Mobile-first responsive:** POS particularly should be tablet-friendly (touch targets ≥ 44px, larger fonts)
- **PWA:** offline-capable POS for spotty connectivity, background sync when online
- **Multi-currency & FX:** if expanding beyond domestic market
- **Accounting integration:** export to QuickBooks/Xero/CSV
- **Audit log UI:** dedicated page surfacing `activity_logs` with filters (currently model exists but no UI)
- **Image management:** CDN, multiple images per product, image variants
- **Backup & restore:** automated DB backups with restore runbook
- **i18n:** if multi-language is needed (currently English-only)
- **Two-factor authentication:** for admin accounts
- **API rate limiting:** throttle per-user to prevent abuse
- **Webhooks:** outbound webhooks for partners

---

## Sequencing & Dependencies

```
Phase 2 (Sales/POS)
   ↓ enables
Phase 3 (PO & Receiving) ── feeds accurate cost data into ── Phase 5 (Reports)
   ↓ feeds                                            ↑
Phase 4 (Inventory Ops) ─────────────────────────────┘
   ↓
Phase 6 (Pricing & Credit) — uses SaleService from Phase 2
   ↓
Phase 7 (Deliveries & Notifications)
```

**Recommended execution order** if doing multiple phases in one session:
Phase 2 → Phase 3 → Phase 5 → Phase 4 → Phase 6 → Phase 7

(Sales first because it's the load-bearing flow; Reports next because it justifies the others; Deliveries last because it depends on completed sales.)

---

## Cross-Cutting Standards (apply to every phase)

These conventions are already established in Phase 1 and MUST be followed:

1. **Tests** — every backend slice ships with `tests/Feature` and/or `tests/Unit` coverage. Target ratio ≥ 1.0. Run `php artisan test --parallel` before declaring done.
2. **Transactions** — any operation touching `batches`, `stock_movements`, `sales`, or `purchase_orders` wraps in `DB::transaction` and uses `lockForUpdate` on batch rows.
3. **Anti-flaky tests** — never assert specific ordering of concurrent operations; assert membership or aggregate counts.
4. **Role middleware** — every new route explicitly declares allowed roles via `->middleware('role:...')`.
5. **Form Requests** — every POST/PUT uses a `StoreXxxRequest` / `UpdateXxxRequest`; never inline validation in controllers.
6. **Resources** — every JSON response uses an `XxxResource`; never return raw models.
7. **Stock movement audit** — every quantity change (sale, receiving, adjustment, transfer) creates exactly one `StockMovement` row referencing the source transaction via morph.
8. **FEFO reuse** — never reimplement batch selection; always call `FefoBatchSelector`.
9. **Pinia stores** — every Vue page backed by a `resources/js/stores/<resource>.js` store; no inline fetches in components.
10. **Build verification** — `npm run build` succeeds and `php artisan test` passes before any slice is marked complete.

---

## Out-of-Scope Reminders (do NOT pull these into phase work)

- POS hardware integration (receipt printers, barcode scanners, cash drawers) — defer until retail pilot
- AI/demand forecasting — defer until 12+ months of sales history
- Marketplace sync (Tokopedia/Shopee) — separate ferment
- E-commerce storefront — separate project

---

## Decision Log (proposed for upcoming phases)

| Decision | Proposed Choice | Rationale | Rejected Alternatives |
|---|---|---|---|
| Sale → batch allocation | Service class with `lockForUpdate` | Prevents race conditions on hot SKUs | Pure application-level check (flaky), DB triggers (opaque) |
| Invoice numbering | `YYYYMM-NNNN` per warehouse | Human-readable, audit-friendly | UUID (ugly for customers), sequential global (couples warehouses) |
| Payment recording | Separate `sale_payments` table | Multi-method per sale (split tender) | JSON column (loses queryability) |
| Stock movement type | Enum on `type` column | Stable, queryable, indexed | Free-text (error-prone) |
| Credit enforcement | Block at sale creation | Fail-fast UX | Allow over-limit, collect later (risk) |
| Notifications transport | Database + email (queued) | Standard Laravel pattern, testable | WebSockets (complex), SMS (cost) |
| Dashboard charts | Lightweight: SVG or `chart.js` | Minimal bundle cost | D3 (overkill), ApexCharts (heavy) |

---

## Estimated Effort (rough)

| Phase | Slices | Backend files | Frontend files | Tests |
|------:|------:|--------------:|---------------:|------:|
| 2 | 3 | ~15 | ~10 | ~20 |
| 3 | 2 | ~10 | ~4 | ~10 |
| 4 | 2 | ~8 | ~4 | ~8 |
| 5 | 2 | ~5 | ~3 | ~8 |
| 6 | 2 | ~8 | ~4 | ~10 |
| 7 | 3 | ~10 | ~6 | ~12 |
| **Total** | **14** | **~56** | **~31** | **~68** |

Treat these as ballpark, not commitments. Actual scope will be re-estimated at the start of each phase.

---

## How to Use This Roadmap

1. **Pick the next phase** based on business priority (usually Phase 2 — Sales/POS).
2. **Scope one slice at a time** using the `propose_ferment_scoping` flow.
3. **Build with delegation:** each slice's plan gets broken into 1–3 chunks, delegated to Builder agents with the complexity tier matching the chunk.
4. **Review after each phase**, not after each slice — a phase is the natural review boundary.
5. **Re-snapshot after each phase** so the roadmap reflects what's actually shipped.

---

**Status:** Awaiting approval. Once approved, the next step is to scope Phase 2 Slice 2.1 (Sales data model & API) as a ferment.
