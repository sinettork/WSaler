# Cambodia Address Cascader — Design

**Date**: 2026-06-27
**Status**: Approved
**Scope**: Replace the simple `address` text field in Customer, Supplier, and Warehouse forms with a Taobao/Shopee-style cascading address selector for Cambodia (Province → District → Commune → Village), with bilingual (English + Khmer) labels.

## Goals

- Users can pick a structured Cambodian address in 4 cascading dropdowns.
- Display full hierarchical address in list views (e.g. "Street, Village, Commune, District, Province").
- Backward compatible: existing rows with free-text `address` continue to work unchanged.
- Reusable component so Customer, Supplier, and Warehouse forms all share one implementation.

## Non-Goals (YAGNI)

- Postcodes / postal codes
- Geocoding or lat/lng
- Reverse address lookup
- Address change audit history
- Other countries' administrative divisions
- Autocomplete-from-free-text on legacy `address` rows

## Decisions Made (locked during brainstorming)

| Decision | Choice |
|---|---|
| Where to apply | Customer + Supplier + Warehouse forms |
| Data model | Add nullable FK columns; keep existing `address` text field |
| Dataset | Full 4-level hierarchy (province/district/commune/village), bilingual EN + KM |

---

## 1. Architecture Overview

### Backend (Laravel)

- 4 normalized tables: `provinces`, `districts`, `communes`, `villages`. Each has `code`, `name_en`, `name_km`, and a `parent_id` FK (nullable on provinces).
- 4 Eloquent models under `App\Models\Addresses\` with `HasMany`/`BelongsTo` relationships and a `getNameAttribute()` accessor that returns the localized name based on `app()->getLocale()`.
- 4 cascading REST endpoints under `/api/addresses/` (public — no auth):
  - `GET /api/addresses/provinces`
  - `GET /api/addresses/provinces/{province}/districts`
  - `GET /api/addresses/districts/{district}/communes`
  - `GET /api/addresses/communes/{commune}/villages`
- All endpoints accept `?q=…` for case-insensitive substring search across `name_en` and `name_km`.
- Province list cached for 24h via `Cache::remember('addresses:provinces:' . $locale, …)`.

### Frontend (Vue)

- One reusable `AddressCascader.vue` component in `resources/js/components/`.
- 4 `<BaseSelect>` instances rendered in order; lower levels disabled until parent is chosen.
- Commune (≥30 options) and Village (≥30 options) lists get a search/typeahead filter.
- Emits a flat `{ province_id, district_id, commune_id, village_id, address }` object.
- Pinia store `resources/js/stores/addresses.js` handles fetching with in-memory caching per session.

### Data model changes (existing tables)

- Add nullable `province_id`, `district_id`, `commune_id`, `village_id` FK columns to `customers`, `suppliers`, `warehouses`.
- Keep the existing `address` text field unchanged.

### Dataset source

- JSON seed file at `database/seeders/data/kh-addresses.json` (≈16k rows, ~2 MB), sourced from Open Development Cambodia (CC-BY-SA).
- Each row is a denormalized village with its province/district/commune; the seeder dedupes parents on load.

---

## 2. Backend Detail

### 2.1 Migrations

**`create_provinces_table`**
```
id, code (string, unique), name_en (string), name_km (string),
type (enum: 'province'|'municipality'), sort_order (int, default 0), timestamps
```
Indexes: `code` unique.

**`create_districts_table`**
```
id, code (string), province_id (fk), name_en, name_km,
sort_order (int, default 0), timestamps
```
Indexes: `(province_id, code)` unique, `name_en`, `name_km`.

**`create_communes_table`**
```
id, code (string), district_id (fk), name_en, name_km,
sort_order (int, default 0), timestamps
```
Indexes: `(district_id, code)` unique, `name_en`, `name_km`.

**`create_villages_table`**
```
id, code (string), commune_id (fk), name_en, name_km,
sort_order (int, default 0), timestamps
```
Indexes: `(commune_id, code)` unique, `name_en`, `name_km`.

**`add_address_fks_to_customers_table`**, **`…_suppliers_table`**, **`…_warehouses_table`**

Add 4 nullable columns (no backfill; existing rows get NULL):
```
province_id (nullable fk -> provinces.id),
district_id  (nullable fk -> districts.id),
commune_id   (nullable fk -> communes.id),
village_id   (nullable fk -> villages.id)
```

All migrations are additive. Rollback is safe (drops new tables / columns).

### 2.2 Models (under `app/Models/Addresses/`)

```php
class Province extends Model {
    protected $fillable = ['code','name_en','name_km','type','sort_order'];
    public function districts(): HasMany { return $this->hasMany(District::class); }
    public function getNameAttribute(): string {
        return app()->getLocale() === 'km' ? $this->name_km : $this->name_en;
    }
}
// District, Commune, Village — same pattern
//   - belongsTo(parent::class, 'parent_id_field')
//   - hasMany(child::class)
//   - localized name accessor
```

### 2.3 Controller — `app/Http/Controllers/Api/AddressController.php`

```
public function indexProvinces(Request $request): JsonResponse
public function indexDistricts(Province $province, Request $request): JsonResponse
public function indexCommunes(District $district, Request $request): JsonResponse
public function indexVillages(Commune $commune, Request $request): JsonResponse
```

Each returns:
```json
{
  "data": [
    { "value": 12, "code": "1201", "label": "Phnom Penh", "label_km": "ភ្នំពេញ" }
  ]
}
```

Province endpoint uses `Cache::remember('addresses:provinces:' . $locale, 86400, fn() => …)`. The other three query live but use indexed lookups; the per-parent result set is bounded and small.

### 2.4 Routes (added to `routes/api.php`)

```php
Route::prefix('addresses')->group(function () {
    Route::get('provinces', [AddressController::class, 'indexProvinces']);
    Route::get('provinces/{province}/districts', [AddressController::class, 'indexDistricts']);
    Route::get('districts/{district}/communes', [AddressController::class, 'indexCommunes']);
    Route::get('communes/{commune}/villages', [AddressController::class, 'indexVillages']);
});
```

No `auth:sanctum`, no `permission:` middleware. Address reference data is not sensitive.

### 2.5 Seeders (`database/seeders/Addresses/`)

- `ProvincesSeeder`, `DistrictsSeeder`, `CommunesSeeder`, `VillagesSeeder`.
- All idempotent (upsert on `code`).
- Dependency order: Provinces → Districts → Communes → Villages.
- `DatabaseSeeder::run()` updated to invoke them in order after existing seeders.
- JSON payload shape (one row per village):
  ```json
  {
    "p_code":"12", "p_type":"municipality",
    "p_name_en":"Phnom Penh", "p_name_km":"ភ្នំពេញ",
    "d_code":"1201",
    "d_name_en":"Doun Penh", "d_name_km":"ដូនពេញ",
    "c_code":"120101",
    "c_name_en":"Sangkat Wat Phnom", "c_name_km":"សង្កាត់វត្តភ្នំ",
    "v_code":"12010101",
    "v_name_en":"Phsar Kandal", "v_name_km":"ផ្សារកណ្តាល"
  }
  ```

### 2.6 Validation rules

Added to `StoreCustomerRequest`, `UpdateCustomerRequest`, `StoreSupplierRequest`, `UpdateSupplierRequest`, `StoreWarehouseRequest`, `UpdateWarehouseRequest`:
```php
'province_id' => 'nullable|exists:provinces,id',
'district_id'  => 'nullable|exists:districts,id',
'commune_id'   => 'nullable|exists:communes,id',
'village_id'   => 'nullable|exists:villages,id',
'address'      => 'nullable|string|max:500',
```

### 2.7 Resources

`CustomerResource`, `SupplierResource`, `WarehouseResource` updated to eager-load `province`, `district`, `commune`, `village` and expose their localized names so list views can render the full address without extra joins.

---

## 3. Frontend Detail

### 3.1 `resources/js/components/AddressCascader.vue`

**Props**
```ts
{
  modelValue: {
    province_id: number|null,
    district_id: number|null,
    commune_id:  number|null,
    village_id:  number|null,
    address:     string
  },
  required?: {
    province?: boolean, district?: boolean,
    commune?: boolean, village?: boolean
  },
  errors?: Record<string, string>,
  showAddressField?: boolean   // default true
}
```

**Emits**
```ts
update:modelValue  // same shape, on every change
```

**Behavior**
- On mount: fetch provinces (cached by store).
- Watch each parent: when changed, reset descendants and refetch.
- Child selects disabled until parent has a value.
- Commune & Village (≥30 options) get a client-side typeahead filter on top of the store's cached list.
- Loading state shown inside each select during fetch.

### 3.2 `resources/js/stores/addresses.js`

```js
export const useAddressesStore = defineStore('addresses', () => {
  const provinces = ref([])        // loaded once per session
  const childCache = ref({})       // 'provinces:12' -> [...]

  async function loadProvinces() { … }
  async function loadDistricts(provinceId, search='') { … }
  async function loadCommunes(districtId, search='') { … }
  async function loadVillages(communeId, search='') { … }
})
```

Child cache stores unfiltered lists per parent for the session. `?q=` filtering is applied client-side from cache.

### 3.3 Form integration

In `CustomerForm.vue`, `SupplierForm.vue`, `WarehouseForm.vue`:

1. Replace the existing `<textarea>` address block with:
   ```vue
   <AddressCascader
     v-model="form.addresses"
     :required="{ province: true, district: true, commune: true }"
     :errors="errors"
   />
   ```
2. Extend `form` reactive object:
   ```js
   addresses: {
     province_id: null, district_id: null,
     commune_id: null,  village_id: null,
     address: ''
   }
   ```
3. `save()` payload spreads structured fields onto the request:
   ```js
   payload.province_id = form.addresses.province_id
   payload.district_id = form.addresses.district_id
   payload.commune_id  = form.addresses.commune_id
   payload.village_id  = form.addresses.village_id
   payload.address     = form.addresses.address
   ```
4. Edit-mode loader populates `form.addresses` from server-side eager-loaded FK fields.
5. List views (`Customers.vue`, `Suppliers.vue`, `Warehouses.vue`) render:
   ```
   #123, St. 271 • Phnom Penh • Doun Penh • Sangkat Wat Phnom
   ```
   Fall back to raw `address` text when no structured data is set.

---

## 4. Migration Order, Testing, Rollout

### 4.1 Migration order

Run in this order in a single deploy:
1. `php artisan make:migration create_provinces_table`
2. `php artisan make:migration create_districts_table`
3. `php artisan make:migration create_communes_table`
4. `php artisan make:migration create_villages_table`
5. `php artisan make:migration add_address_fks_to_customers_table`
6. `php artisan make:migration add_address_fks_to_suppliers_table`
7. `php artisan make:migration add_address_fks_to_warehouses_table`
8. `php artisan db:seed --class=ProvincesSeeder --class=DistrictsSeeder --class=CommunesSeeder --class=VillagesSeeder`

### 4.2 Testing

**Feature tests** (`tests/Feature/`)
- `AddressApiTest.php`
  - `GET /api/addresses/provinces` returns ≥ 25 items, localized via `Accept-Language`.
  - `GET /api/addresses/provinces/{id}/districts` returns only that province's districts.
  - `?q=` search filters across `name_en` and `name_km`.
  - 404 when parent id doesn't exist.
- `CustomerAddressTest.php` / `SupplierAddressTest.php` / `WarehouseAddressTest.php`
  - Creating with all 4 FKs persists correctly.
  - Validation rejects non-existent FK ids.
  - Edit restores the cascader selection.

**Component test** (Vitest + @vue/test-utils; add minimally if not present)
- `AddressCascader.spec.js`
  - Selecting a province loads districts and resets lower levels.
  - Disabling parent makes child disabled.
  - Emits `update:modelValue` with full object on every change.

**Manual smoke** (documented in spec, run before merging)
- Create a customer end-to-end with all 4 levels selected; verify it shows in the list.
- Create with only province + district (warehouse scenario); verify it saves and shows.
- Edit a legacy row with only `address` text; verify selectors load empty and can be augmented.

### 4.3 Permissions / access control

- Address endpoints: **public**, no auth.
- Customer/Supplier/Warehouse endpoints: **unchanged** — existing `view/create/edit` permissions continue to apply; new FK fields are validated inside existing requests.

### 4.4 Rollout

- No breaking API changes (FK fields are additive; `address` field still works).
- Existing rows display unchanged; only newly created or re-saved rows get structured data.
- Province cache warms on first request (~25 rows, instant). Optional: `php artisan addresses:warm-cache` command for proactive warm-up.

---

## 5. File Touch List

### Backend (new)
- `database/migrations/*_create_provinces_table.php`
- `database/migrations/*_create_districts_table.php`
- `database/migrations/*_create_communes_table.php`
- `database/migrations/*_create_villages_table.php`
- `database/seeders/data/kh-addresses.json`
- `database/seeders/Addresses/ProvincesSeeder.php`
- `database/seeders/Addresses/DistrictsSeeder.php`
- `database/seeders/Addresses/CommunesSeeder.php`
- `database/seeders/Addresses/VillagesSeeder.php`
- `app/Models/Addresses/Province.php`
- `app/Models/Addresses/District.php`
- `app/Models/Addresses/Commune.php`
- `app/Models/Addresses/Village.php`
- `app/Http/Controllers/Api/AddressController.php`
- `tests/Feature/AddressApiTest.php`
- `tests/Feature/CustomerAddressTest.php`
- `tests/Feature/SupplierAddressTest.php`
- `tests/Feature/WarehouseAddressTest.php`

### Backend (modified)
- `routes/api.php` — add address routes
- `database/seeders/DatabaseSeeder.php` — call address seeders in order
- `database/migrations/*_add_address_fks_to_customers_table.php`
- `database/migrations/*_add_address_fks_to_suppliers_table.php`
- `database/migrations/*_add_address_fks_to_warehouses_table.php`
- `app/Http/Requests/StoreCustomerRequest.php`, `UpdateCustomerRequest.php`
- `app/Http/Requests/StoreSupplierRequest.php`, `UpdateSupplierRequest.php`
- `app/Http/Requests/StoreWarehouseRequest.php`, `UpdateWarehouseRequest.php`
- `app/Http/Resources/CustomerResource.php`, `SupplierResource.php`, `WarehouseResource.php`
- `app/Models/Customer.php`, `Supplier.php`, `Warehouse.php` — add relationships

### Frontend (new)
- `resources/js/components/AddressCascader.vue`
- `resources/js/stores/addresses.js`
- `resources/js/components/__tests__/AddressCascader.spec.js` (if Vitest added)

### Frontend (modified)
- `resources/js/pages/master/CustomerForm.vue`
- `resources/js/pages/master/SupplierForm.vue`
- `resources/js/pages/master/WarehouseForm.vue`
- `resources/js/pages/master/Customers.vue`
- `resources/js/pages/master/Suppliers.vue`
- `resources/js/pages/master/Warehouses.vue`
- `resources/js/stores/customers.js`, `stores/suppliers.js`, `stores/warehouses.js` (load FK ids into the form model)

---

## 6. Acceptance Criteria

1. `php artisan migrate` succeeds and the 4 new tables exist with FK columns on customer/supplier/warehouse tables.
2. `php artisan db:seed` populates provinces (≥25), districts (≥200), communes (≥1,500), villages (≥13,000) from `kh-addresses.json`.
3. `GET /api/addresses/provinces` returns a localized list with `label` and `label_km` fields.
4. Cascading endpoints return only the children of the given parent, and `?q=` filters by both `name_en` and `name_km`.
5. In Customer/Supplier/Warehouse forms, selecting a province loads its districts; selecting a district loads its communes; selecting a commune loads its villages.
6. Lower-level selects are disabled until the parent has a value.
7. Saving a customer persists all 4 FK ids along with the free-text `address`.
8. Editing a customer restores the cascader selections.
9. List views render "Street • Village • Commune • District • Province" or fall back to the raw `address` text.
10. Switching the UI locale to Khmer shows Khmer names in the cascader labels.
11. All new feature tests pass; existing tests still pass (no regressions).
