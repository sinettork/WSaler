# Cambodia Address Cascader — Amendment: Outside-Cambodia Manual Mode

**Status:** Draft amendment (pending merge into spec)
**Date:** 2026-06-28
**Author:** Audit/follow-up
**Affects:** `docs/superpowers/specs/2026-06-27-cambodia-address-cascader-design.md`
**Plan impact:** `docs/superpowers/plans/2026-06-27-cambodia-address-cascader.md` — new task blocks added.

---

## Rationale

The original spec lists "Other countries' administrative divisions" as a non-goal. In practice, wholesale operations routinely involve cross-border suppliers (Thai, Vietnamese, Chinese), export customers, and regional warehouses outside Cambodia. With the current spec, a user creating a Supplier located in Bangkok or a Customer in Ho Chi Minh City has no path through the cascader — Cambodia simply does not apply.

This amendment adds a single explicit "outside Cambodia" mode that bypasses the cascader and captures the address as free text, without breaking the Cambodia-only UX for the common case.

---

## Decisions Made

| Decision | Choice | Rationale |
|---|---|---|
| UX trigger | Single checkbox "Address is outside Cambodia" | Explicit, no ambiguity; maps cleanly to data state |
| Country representation | Free-text string (e.g. "Vietnam", "Thailand") | User-friendly; no ISO lookup table needed |
| Schema columns | Add `country` (nullable string 100) + `address_line2` (nullable string 500) | Two columns; covers common non-Cambodia layout (street + unit/floor) |
| Cambodia-row UX | Unchanged — 4 cascading selects still primary | Backwards compatible; no migration of legacy data needed |
| Validation mode-switch | If `country` non-empty → FK columns must be NULL, `address` required | Prevents mixed Cambodia + foreign data on one row |

---

## Changes to the Spec

### Status

Change from:
> **Status**: Approved

To:
> **Status**: Approved (amended 2026-06-28 — see `.kimchi/docs/drafts/cambodia-address-cascader-amendment.md`)

### Non-Goals (YAGNI) — replace one bullet

Remove:
> - Other countries' administrative divisions

Replace with:
> - Other countries' administrative divisions (Cambodia-only cascade is supported; non-Cambodia addresses use a single free-text mode — see amendment)

### Section 1 — Architecture Overview, Frontend (Vue)

Add a bullet after the existing "Pinia store ..." bullet:

> - When the user toggles "Address is outside Cambodia", the cascader hides and a free-text mode (Country / City / Street / Address line 2) takes its place. The toggle is part of `AddressCascader.vue`.

### Section 1 — Data model changes (existing tables)

Replace the bullet list with:

> - Add nullable `province_id`, `district_id`, `commune_id`, `village_id` FK columns to `customers`, `suppliers`, `warehouses`.
> - Add nullable `country` (string, max 100) and `address_line2` (string, max 500) columns to `customers`, `suppliers`, `warehouses`.
> - Keep the existing `address` text field unchanged.
> - Convention: a row is "Cambodia-mode" when `country` is null/empty (cascader applies, FK columns may be set). A row is "outside-Cambodia mode" when `country` is non-empty (FK columns must be NULL, `address` is required).

### Section 2.1 — Migrations

After the `add_address_fks_to_*_table` migrations, add three new migrations:

> **`add_country_and_address_line2_to_customers_table`**, **`…_suppliers_table`**, **`…_warehouses_table`**
>
> Add 2 nullable columns (no backfill; existing rows get NULL on `country` and `address_line2`):
> ```
> country      (nullable string 100)
> address_line2 (nullable string 500)
> ```
>
> All migrations are additive. Rollback is safe (drops new columns).

### Section 2.6 — Validation rules

Replace the rules block with:

```php
'province_id'    => 'nullable|exists:provinces,id',
'district_id'    => 'nullable|exists:districts,id',
'commune_id'     => 'nullable|exists:communes,id',
'village_id'     => 'nullable|exists:villages,id',
'country'        => 'nullable|string|max:100',
'address_line2'  => 'nullable|string|max:500',
'address'        => 'nullable|string|max:500',

// Conditional rule (implemented via `withValidator` in the FormRequest, not as a flat array):
// - If `country` is non-empty:
//     * `address` becomes required
//     * all 4 FK columns must be absent (null)
// - If `country` is null/empty:
//     * Cambodia rules apply (FK columns optional, `address` optional)
```

### Section 2.7 — Resources

Add one sentence at the end:

> When `country` is non-empty, the resource includes the country name and `address_line2` so list views can render non-Cambodia rows without extra joins.

### Section 3.1 — `AddressCascader.vue`

Replace the **Props** block with:

```ts
{
  modelValue: {
    country:        string|null,    // null/empty = Cambodia mode
    province_id:    number|null,
    district_id:    number|null,
    commune_id:     number|null,
    village_id:     number|null,
    address:        string,
    address_line2?: string
  },
  required?: {
    province?: boolean, district?: boolean,
    commune?: boolean,  village?: boolean
  },
  errors?: Record<string, string>,
  showAddressField?: boolean,   // default true
  showCountryToggle?: boolean   // default true
}
```

Replace the **Emits** block with:

```ts
update:modelValue  // same shape, on every change
```

Append to the **Behavior** list:

> - **Outside-Cambodia mode.** A checkbox "Address is outside Cambodia" sits above the 4 selects. When checked, the cascader hides and 4 free-text inputs appear: Country (text, feeds `country`), City (text, folded into `address` on submit), Street address (textarea, feeds `address`), Address line 2 (text, optional, feeds `address_line2`). Toggling the checkbox resets descendants appropriately — switching from "outside" back to Cambodia clears `country`/`address_line2` but preserves the cascader state if any FKs were set before.
> - When the parent form's `country` is non-empty, the component initializes in outside-Cambodia mode.

### Section 3.3 — Form integration

Replace step 2 with:

```js
addresses: {
  country: '',         // '' = Cambodia mode
  province_id: null, district_id: null,
  commune_id: null,    village_id: null,
  address: '',
  address_line2: ''
}
```

Insert a new step 3a (before the existing step 3):

```js
// 3a. Save payload (extends existing step 3)
payload.country        = form.addresses.country || null
payload.address_line2  = form.addresses.address_line2 || null
payload.province_id    = form.addresses.province_id
payload.district_id    = form.addresses.district_id
payload.commune_id     = form.addresses.commune_id
payload.village_id     = form.addresses.village_id
payload.address        = form.addresses.address
```

Replace step 5 with:

```
5. List views (Customers.vue, Suppliers.vue, Warehouses.vue) render:
   - Cambodia rows: "#123, St. 271 • Phnom Penh • Doun Penh • Sangkat Wat Phnom"
     Falls back to raw `address` text when no structured data is set.
   - Non-Cambodia rows (country non-empty): "<address> [• <address_line2>] • <country>"
     Example: "123 Le Loi, District 1 • Floor 3 • Vietnam"
```

### Section 4.1 — Migration order

Append three new steps at the tail:

```
9. `php artisan make:migration add_country_and_address_line2_to_customers_table`
10. `php artisan make:migration add_country_and_address_line2_to_suppliers_table`
11. `php artisan make:migration add_country_and_address_line2_to_warehouses_table`
```

### Section 4.2 — Testing

Append a new bullet block under **Feature tests**:

```
- `OutsideCambodiaAddressTest.php` (one file, covers all three entities)
  - Customer/Supplier/Warehouse: creating with `country` non-empty + `address` saves
    correctly with all 4 FK columns NULL and `address_line2` persisted when supplied.
  - Validation rejects mixed rows: setting `country` + any of the 4 FK columns returns 422.
  - Validation rejects non-Cambodia rows missing `address` (422).
  - Edit flow: loading a non-Cambodia row restores Country / City / Street / Line 2
    inputs and the toggle stays checked.
  - Cambodia-row round-trip: a Cambodia row (country = null) still loads and saves
    with no behavioural change (regression guard).
```

### Section 5 — File Touch List

**Backend (modified)** — append:

```
- `database/migrations/*_add_country_and_address_line2_to_customers_table.php`
- `database/migrations/*_add_country_and_address_line2_to_suppliers_table.php`
- `database/migrations/*_add_country_and_address_line2_to_warehouses_table.php`
- `app/Models/Customer.php`, `Supplier.php`, `Warehouse.php` — fillable additions for `country` and `address_line2`
```

**Backend (new)** — append:

```
- `tests/Feature/OutsideCambodiaAddressTest.php`
```

**Frontend (modified)** — append:

```
- `resources/js/pages/master/CustomerForm.vue` — `country` and `address_line2` in form model + payload
- `resources/js/pages/master/SupplierForm.vue`  — same
- `resources/js/pages/master/WarehouseForm.vue` — same
- `resources/js/pages/master/Customers.vue`     — non-Cambodia row rendering
- `resources/js/pages/master/Suppliers.vue`     — non-Cambodia row rendering
- `resources/js/pages/master/Warehouses.vue`    — non-Cambodia row rendering
- `resources/js/stores/customers.js`, `stores/suppliers.js`, `stores/warehouses.js` —
  load `country` and `address_line2` into the form model on edit
```

### Section 6 — Acceptance Criteria

Append:

```
12. Customer/Supplier/Warehouse forms display an "Address is outside Cambodia"
    checkbox above the cascader; checking it hides the 4 selects and reveals
    Country / City / Street / Address line 2 inputs.
13. Saving a non-Cambodia row stores `country` non-empty, all 4 FK columns NULL,
    `address` non-empty, and `address_line2` exactly as supplied.
14. Validation rejects mixed rows (country + any FK) with 422 and a clear error.
15. List views render non-Cambodia rows as "<address> [• <address_line2>] • <country>"
    without breaking the Cambodia hierarchical layout.
16. Existing Cambodia rows still load and display correctly after the migration
    (`country` defaults to NULL, cascader unchanged).
17. All new feature tests pass; existing tests still pass (no regressions).
```

---

## Risk & Blast Radius

- **API contract:** Purely additive. New optional request fields; new optional response fields on resources. No existing field shape changes.
- **Data integrity:** Backend `withValidator` rule rejects mixed rows (country + FKs) at the request boundary. The DB has no FK constraint between `country` and the 4 geographic FKs, so the application layer is the only enforcement point — a test must lock this in.
- **Backwards compatibility:** Legacy rows have no `country` column. Migration defaults `country` to NULL, so all legacy rows render as Cambodia mode. The 4 FK columns remain nullable, so legacy free-text-only rows still load cleanly.
- **Locale:** Switching the UI to Khmer does not translate the free-text Country / City / Street / Line 2 fields — the user types them in their own language. This is acceptable because the column is free text and the customer/supplier's address is in their local script.

---

## Estimated Effort

| Slice | Files | Effort |
|---|---|---|
| 3 migrations (add country + line 2) | 3 | trivial |
| AddressCascader.vue toggle UI + props/emits | 1 | small |
| 3 form integrations (CustomerForm / SupplierForm / WarehouseForm) | 3 | small |
| 3 list-view render updates | 3 | small |
| 3 stores loading country + line 2 | 3 | trivial |
| Resource exposure of country + line 2 | 3 | trivial |
| 1 feature test (OutsideCambodiaAddressTest) | 1 | small |
| FormRequest `withValidator` conditional | 6 | small |

Roughly half a day of focused work. Independent of the Cambodia-cascade implementation tasks; can run in parallel or be deferred to a Phase 4 amendment task.

---

## Out of Scope (still YAGNI after this amendment)

- Country code picklist / ISO lookup table
- Geocoding or reverse address lookup
- Address verification / postal code validation
- Other countries' administrative divisions (still not in scope — non-Cambodia is text-only)
- Address change audit history
