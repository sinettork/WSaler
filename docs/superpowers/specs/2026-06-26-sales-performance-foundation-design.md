# Sales Performance Management — Foundation Layer Design

**Date:** 2026-06-26
**Sub-project:** 1 of 4 (Foundation: Salespeople + Targets + Customer Assignment)
**Status:** Draft, awaiting user approval

---

## 1. Goal

Build the foundation layer of Sales Performance Management for WSaler:

- Manage salespeople (profiles, branch/team/territory assignments, employment status).
- Set and track sales targets across daily/weekly/monthly/quarterly/annual periods and 7 metrics, with real-time achievement updates triggered by sales.
- Manage customer-to-salesperson assignments with audit history and approval workflow.

This is **Sub-project 1 of 4**. Subsequent sub-projects will add Commission Rules + Settlement + Incentives, KPI Dashboard + Leaderboard + Reports, and Visit Tracking + Sales Pipeline.

---

## 2. Scope (In / Out)

### In scope
- Salesperson profile (extended from `User` with `role='salesperson'` and `employment_status` enum).
- Branch assignment (branch = warehouse; existing pattern).
- Team CRUD + leader assignment + team membership.
- Territory CRUD + many-to-many salesperson membership.
- Customer assignment (proper `customer_assignments` pivot with notes, valid_from/valid_to, status, audit).
- Approval workflow for assignments (reuses existing `Approval` model).
- Sales target CRUD: header + per-metric lines.
- Target templates + bulk-assignment (apply template to many SPVs).
- Time-series target achievement (`sales_target_achievements`).
- Real-time achievement updates via `SaleObserver` → `TargetAchievementUpdater` service.
- Reverse on sale void/cancel.
- Customer visit history (read-only view: aggregate sales + future visits).
- i18n (en + id) and currency awareness (reuse existing helpers).
- Activity log entries on assignment mutations.

### Out of scope (later sub-projects)
- Commission rules, calculation, settlement, payment → Sub-project 2.
- KPI dashboard cards, leaderboard, reports → Sub-project 3.
- Visit logging with GPS/photos, sales pipeline kanban → Sub-project 4.

---

## 3. Architecture

### Backend (Laravel 13)

- **Namespace:** `App\Models\SalesPerformance\`, `App\Services\SalesPerformance\`, `App\Http\Controllers\Api\SalesPerformance\`.
- **Observers:** `App\Observers\SaleObserver` registered in `AppServiceProvider::boot()`.
- **Routes:** under `/api/sales-performance/*` in `routes/api.php`.
- **Auth:** existing Sanctum + RBAC permission middleware (`salespeople.manage`, `targets.manage`, `assignments.manage`, `approvals.review`, etc.).
- **FormRequests** for input validation; **Resources** for response shaping.
- **Soft deletes** on salespeople (via User), teams, territories, customer_assignments, sales_targets.

### Frontend (Vue 3 SPA)

- New module: `resources/js/pages/sales-performance/` with sub-folders per domain.
- Pinia stores: `resources/js/stores/salesPerformance/{salespeople,teams,territories,targets,assignments,approvals}.js`.
- API client: `resources/js/services/salesPerformanceApi.js` (axios).
- Router entries under `/sales-performance/*` (lazy-loaded).
- Sidebar entry "Sales Performance" with sub-items, added to `AppSidebar.vue`.
- i18n keys in `resources/js/i18n/{en,id}/sales-performance.json`.

### Cross-cutting
- Use existing `Approval` model (polymorphic subject) for assignment approvals.
- Use existing `ActivityLog` model for audit trail.
- Use existing `RolePermissionSeeder` pattern for new permissions.
- No new composer or npm packages.

---

## 4. Data Model

### 4.1 Schema changes

| Table | Operation | Columns |
|---|---|---|
| `users` | ALTER | ADD `employment_status` enum(`active`,`inactive`,`on_leave`,`terminated`) DEFAULT 'active'; ADD `team_id` BIGINT NULL FK → `teams.id` |
| `users` | ALTER | DROP `customer_ids` (after data migration) |
| `teams` | CREATE | id, name, code UNIQUE, leader_user_id NULL FK → users.id, description, is_active BOOL, timestamps, soft deletes |
| `territories` | CREATE | id, name, code UNIQUE, region, description, is_active BOOL, timestamps, soft deletes |
| `territory_user` | CREATE | id, territory_id FK, user_id FK, assigned_at, assigned_by FK, valid_from DATE, valid_to DATE NULL, timestamps |
| `customer_assignments` | CREATE | id, customer_id FK, salesperson_user_id FK, status enum(`pending`,`active`,`expired`,`revoked`), valid_from DATE, valid_to DATE NULL, notes TEXT, assigned_by FK, approval_id FK NULL → approvals.id, timestamps, soft deletes. UNIQUE INDEX (customer_id, salesperson_user_id) WHERE valid_to IS NULL |
| `target_templates` | CREATE | id, name, period_type enum(`daily`,`weekly`,`monthly`,`quarterly`,`annual`), description, is_active BOOL, created_by FK, timestamps, soft deletes |
| `target_template_lines` | CREATE | id, target_template_id FK, metric enum(`sales_amount`,`invoice_count`,`customer_count`,`quantity`,`gross_profit`,`collection_amount`,`new_customer_count`), default_value DECIMAL(18,4), order_index INT, timestamps |
| `sales_targets` | CREATE | id, salesperson_user_id FK, period_type enum, period_start DATE, period_end DATE, target_template_id FK NULL, name, status enum(`draft`,`active`,`achieved`,`expired`,`cancelled`), created_by FK, approved_by FK NULL, timestamps, soft deletes. UNIQUE (salesperson_user_id, period_type, period_start) |
| `sales_target_lines` | CREATE | id, sales_target_id FK, metric enum (same 7), target_value DECIMAL(18,4), timestamps. UNIQUE (sales_target_id, metric) |
| `sales_target_achievements` | CREATE | id, sales_target_line_id FK, snapshot_date DATE, achieved_value DECIMAL(18,4), achievement_pct DECIMAL(8,4), computed_at TIMESTAMP. UNIQUE (sales_target_line_id, snapshot_date). INDEX (snapshot_date) |

### 4.2 Model relationships

- `User` (salesperson role):
  - `belongsTo Team` (via `team_id`)
  - `belongsToMany Territory` (via `territory_user`)
  - `hasMany CustomerAssignment` (as salesperson)
  - `hasMany SalesTarget`
- `Team`:
  - `belongsTo User` (leader)
  - `hasMany User`
- `Territory`:
  - `belongsToMany User` (salespeople)
- `Customer`:
  - `hasMany CustomerAssignment` (history)
  - `hasOne CustomerAssignment` (current active)
- `CustomerAssignment`:
  - `belongsTo Customer`, `belongsTo User` (salesperson), `belongsTo User` (assigned_by), `belongsTo Approval`
- `SalesTarget`:
  - `belongsTo User` (salesperson), `belongsTo TargetTemplate`, `belongsTo User` (creator, approver)
  - `hasMany SalesTargetLine`
- `SalesTargetLine`:
  - `belongsTo SalesTarget`, `hasMany SalesTargetAchievement`
- `SalesTargetAchievement`:
  - `belongsTo SalesTargetLine`

---

## 5. API Contracts

All routes under `/api/sales-performance/*`. JSON request/response. Sanctum auth + RBAC permission middleware. FormRequests validate input; Resources shape output.

### Salespeople

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/salespeople` | `salespeople.view` | filters: `status`, `team_id`, `territory_id`, `branch_id`, `q` (search name/email) |
| POST | `/salespeople` | `salespeople.manage` | body: name, email, password, role='salesperson', employment_status, team_id, branch_id |
| GET | `/salespeople/{id}` | `salespeople.view` | includes: targets (active+recent), customer_assignments (active), team, territories |
| PATCH | `/salespeople/{id}` | `salespeople.manage` | update profile, employment_status, team_id, branch_id |
| POST | `/salespeople/{id}/territories` | `salespeople.manage` | attach territories (replaces list); body: `territory_ids[]` |

### Teams

| Method | Path | Permission |
|---|---|---|
| GET | `/teams` | `teams.view` |
| POST | `/teams` | `teams.manage` |
| GET | `/teams/{id}` | `teams.view` |
| PATCH | `/teams/{id}` | `teams.manage` |
| DELETE | `/teams/{id}` | `teams.manage` |

### Territories

| Method | Path | Permission |
|---|---|---|
| GET | `/territories` | `territories.view` |
| POST | `/territories` | `territories.manage` |
| GET | `/territories/{id}` | `territories.view` |
| PATCH | `/territories/{id}` | `territories.manage` |
| POST | `/territories/{id}/members` | `territories.manage` |
| DELETE | `/territories/{id}/members/{userId}` | `territories.manage` |

### Customer Assignments

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/customers/assignments` | `assignments.view` | filters: salesperson_id, customer_id, status |
| POST | `/customers/assignments` | `assignments.manage` | creates pending assignment + Approval record; assignee = manager role |
| GET | `/customers/assignments/{id}` | `assignments.view` |
| PATCH | `/customers/assignments/{id}` | `assignments.manage` | update notes only; status changes via approve/revoke |
| POST | `/customers/assignments/{id}/revoke` | `assignments.manage` | sets status='revoked', valid_to=today |
| GET | `/customers/{id}/visit-history` | `assignments.view` | read-only aggregate: sales by salesperson for this customer, plus any future visit entries |

### Sales Targets

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/targets` | `targets.view` | filters: salesperson_id, period_type, status, period range |
| POST | `/targets` | `targets.manage` | single create; body: salesperson_user_id, period_type, period_start, lines[{metric, target_value}] |
| GET | `/targets/{id}` | `targets.view` | includes lines + recent achievements (last 30 days) |
| PATCH | `/targets/{id}` | `targets.manage` | name, status (active/cancelled only); cannot change period/lines after approval |
| POST | `/targets/bulk` | `targets.manage` | body: target_template_id, salesperson_user_ids[], period_start → clones lines per SPV |

### Target Templates

| Method | Path | Permission |
|---|---|---|
| GET | `/target-templates` | `targets.view` |
| POST | `/target-templates` | `targets.manage` |
| GET | `/target-templates/{id}` | `targets.view` |
| PATCH | `/target-templates/{id}` | `targets.manage` |
| DELETE | `/target-templates/{id}` | `targets.manage` |

### Approvals

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/approvals` | `approvals.review` | list pending approvals assigned to current user |
| GET | `/approvals/{id}` | `approvals.review` | detail + linked subject |
| POST | `/approvals/{id}/approve` | `approvals.review` | on customer_assignment: activates assignment (status='active', valid_from=today) |
| POST | `/approvals/{id}/reject` | `approvals.review` | body: reason; marks approval rejected, subject remains pending |

### Error responses
Standard JSON: `{ "message": "...", "errors": {...} }` for 422; `{ "message": "..." }` for 403/404/500.

---

## 6. Target Update Flow (Sale → Achievement)

### Service interface

```php
interface TargetAchievementUpdaterInterface {
    public function applySale(Sale $sale): void;
    public function reverseSale(Sale $sale): void;
}
```

Implementation: `App\Services\SalesPerformance\TargetAchievementUpdater`.

### Trigger

`App\Observers\SaleObserver` registered in `AppServiceProvider::boot()`:

```php
Sale::observe(SaleObserver::class);
```

Observer methods:
- `created(Sale $sale)`: if `$sale->status === 'completed'`, call `applySale` inside `DB::transaction`.
- `updated(Sale $sale)`: if status changed from `completed` → `voided`/`canceled`, call `reverseSale`.
- `deleted(Sale $sale)`: call `reverseSale`.

### `applySale` algorithm

1. Begin `DB::transaction` (caller has already opened one; this is a no-op if so).
2. Find active `sales_targets` for `$sale->user_id` where `period_start <= $sale->sold_at <= period_end` AND `status='active'`.
3. For each target, for each line (metric):
   - Compute contribution per metric:

   | Metric | Contribution |
   |---|---|
   | `sales_amount` | `$sale->total` |
   | `invoice_count` | `1` |
   | `customer_count` | `1` if customer exists and not previously counted in this period for this target |
   | `quantity` | `sum(items.quantity)` |
   | `gross_profit` | `sum((item.price - item.cost) * item.quantity)`; cost = batch FIFO or product avg cost |
   | `collection_amount` | `sum(sale_payments.amount where status='completed')` |
   | `new_customer_count` | `1` if `$sale->customer` was created within this period AND this is the first sale from this SPV to that customer |

4. For each line, upsert `sales_target_achievements` for `snapshot_date = $sale->sold_at->toDateString()`:
   - `achieved_value += contribution`
   - `achievement_pct = achieved_value / target_value * 100`
   - `computed_at = now()`

### `reverseSale` algorithm

Same as `applySale` but subtracts the contribution (negate before upsert).

### Future swap path

The interface allows replacing the synchronous implementation with a queued listener without changing the observer. Not built now; seam preserved.

---

## 7. Frontend Module

### Directory layout

```
resources/js/pages/sales-performance/
├── salespeople/
│   ├── SalespeopleList.vue       # table + filters
│   ├── SalespersonDetail.vue     # profile + targets + customers + team + territory
│   └── SalespersonForm.vue       # create/edit modal
├── teams/
│   ├── TeamsList.vue
│   └── TeamForm.vue
├── territories/
│   ├── TerritoriesList.vue
│   └── TerritoryForm.vue
├── targets/
│   ├── TargetsList.vue
│   ├── TargetDetail.vue          # lines + achievement chart (last 30 days)
│   ├── TargetForm.vue
│   └── BulkAssignDialog.vue      # template + SPV multi-select + period
├── assignments/
│   ├── CustomerAssignmentsList.vue
│   └── AssignmentForm.vue
└── approvals/
    └── ApprovalsInbox.vue

resources/js/stores/salesPerformance/
├── salespeople.js
├── teams.js
├── territories.js
├── targets.js
├── assignments.js
└── approvals.js

resources/js/services/salesPerformanceApi.js
resources/js/i18n/en/sales-performance.json
resources/js/i18n/id/sales-performance.json
```

### Router

Add to `resources/js/router/index.js`:

```js
{
  path: '/sales-performance',
  component: () => import('@/layouts/DefaultLayout.vue'),
  children: [
    { path: 'salespeople', component: () => import('@/pages/sales-performance/salespeople/SalespeopleList.vue') },
    // ... etc
  ]
}
```

### Sidebar

Extend `AppSidebar.vue` with new "Sales Performance" section linking to top-level pages.

---

## 8. Migration Plan

| # | Migration | Purpose |
|---|---|---|
| 1 | `2026_06_26_100000_add_employment_status_and_team_id_to_users_table` | Add columns |
| 2 | `2026_06_26_100100_create_teams_table` | Teams |
| 3 | `2026_06_26_100200_create_territories_table` | Territories |
| 4 | `2026_06_26_100201_create_territory_user_table` | Pivot |
| 5 | `2026_06_26_100300_create_customer_assignments_table` | Assignments |
| 6 | `2026_06_26_100400_create_target_templates_table` | Templates |
| 7 | `2026_06_26_100401_create_target_template_lines_table` | Template lines |
| 8 | `2026_06_26_100500_create_sales_targets_table` | Target headers |
| 9 | `2026_06_26_100501_create_sales_target_lines_table` | Target lines |
| 10 | `2026_06_26_100502_create_sales_target_achievements_table` | Achievements |
| 11 | `2026_06_26_100600_migrate_user_customer_ids_to_customer_assignments` | Data migration |
| 12 | `2026_06_26_100601_drop_customer_ids_from_users_table` | Cleanup |

### Migration #11 detail

Reads every User row with non-null `customer_ids` array; for each customer_id, inserts a row into `customer_assignments` with:
- `salesperson_user_id` = user.id
- `customer_id` = customer.id
- `status` = `'active'`
- `valid_from` = today
- `assigned_by` = system user (or first admin)
- `notes` = `'Migrated from users.customer_ids'`

Wrapped in `DB::transaction`. Logs migration count.

### Seeder

Extend `RolePermissionSeeder` to add:
- `salespeople.view`, `salespeople.manage`
- `teams.view`, `teams.manage`
- `territories.view`, `territories.manage`
- `assignments.view`, `assignments.manage`
- `targets.view`, `targets.manage`
- `approvals.review`

Attach appropriate subset to `manager` and `admin` roles.

---

## 9. Testing Strategy

### Feature tests (`tests/Feature/SalesPerformance/`)

| File | Coverage |
|---|---|
| `SalespeopleTest.php` | CRUD, employment_status transitions, permission gates |
| `TeamsTest.php` | CRUD, leader assignment, member listing |
| `TerritoriesTest.php` | CRUD, member attach/detach |
| `CustomerAssignmentsTest.php` | create+approval flow, revoke, notes update, history |
| `SalesTargetsTest.php` | single create, bulk from template, line uniqueness, period validation |
| `SalesTargetAchievementsTest.php` | sale triggers achievement, void reverses, percentages compute correctly |
| `ApprovalsTest.php` | inbox list, approve activates assignment, reject with reason |
| `BulkAssignmentTest.php` | bulk-assign 5 SPVs from template, verify N target rows + N*7 lines |

### Unit test (`tests/Unit/SalesPerformance/`)

| File | Coverage |
|---|---|
| `TargetAchievementUpdaterTest.php` | per-metric contribution math (sales_amount, invoice_count, customer_count, quantity, gross_profit, collection_amount, new_customer_count); reverse subtraction |

### Coverage targets
- Every API endpoint: ≥1 happy-path test + ≥1 auth/permission failure test.
- `TargetAchievementUpdater`: unit test for each of the 7 metrics + reverse path.

### Manual smoke checklist (post-build)

1. Create team → assign leader.
2. Create territory → attach 3 SPVs.
3. Create salesperson via API (or seeder).
4. Assign customer to SPV → verify pending Approval row created.
5. Log in as manager → approve assignment → verify status='active'.
6. Create target template with 7 metric lines.
7. Bulk-assign template to 5 SPVs for current month.
8. Run a sale for SPV #1 → verify `sales_target_achievements` row created with correct values.
9. Run a second sale for SPV #1 → verify achievement accumulates.
10. Void the first sale → verify achievement decrements.

---

## 10. Risks & Trade-offs

- **Snapshot template application**: bulk-assign clones template lines per SPV at assign time. Re-applying with changes doesn't update existing targets. Acceptable for MVP; can add "re-apply diff" later.
- **Achievement granularity**: time-series at daily snapshot. Not real-time-second granularity. Acceptable for daily/weekly/monthly targets.
- **Customer visit history** is a derived view from sales only until Sub-project 4 adds explicit visit records. No new data model needed for this sub-project.
- **Gross profit cost source**: relies on existing Batch FIFO or Product avg cost. If neither is reliable, gross_profit metric may be inaccurate. Mitigation: compute per-metric in service, easy to swap cost source.
- **Approval workflow coupling**: assignments depend on existing `Approval` model. If approval semantics change (e.g., multi-step), this layer must adapt.
- **User.customer_ids migration**: assumes array values are valid customer IDs that still exist. Stranded IDs would be skipped (logged).

---

## 11. Out of Scope Confirmation

Sub-projects 2, 3, 4 are explicitly out:
- **Sub-project 2:** Commission Rules + Settlement + Incentive Programs.
- **Sub-project 3:** KPI Dashboard + Leaderboard + Sales Reports.
- **Sub-project 4:** Visit & Activity Tracking (GPS, photos, call history) + Sales Pipeline (Lead → Won).

This spec only covers the Foundation Layer (Sub-project 1).
