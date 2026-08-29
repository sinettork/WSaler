# Cambodia Address Cascader — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Taobao/Shopee-style cascading Cambodia address selector (Province → District → Commune → Village, bilingual EN + KM) to Customer, Supplier, and Warehouse forms, backed by 4 normalized tables, public REST endpoints, and one reusable Vue component.

**Architecture:** Additive migration — 4 new geographic tables (`provinces`, `districts`, `communes`, `villages`) seeded from a vendored JSON snapshot, 4 cascading public API endpoints with locale-aware labels, 4 nullable FK columns added to existing `customers`/`suppliers`/`warehouses` tables. One reusable Vue `AddressCascader` component used by all three forms.

**Tech Stack:** Laravel 13 (PHP 8.3, anonymous-class migrations, PHPUnit feature tests), Vue 3 + Pinia + Vite, Tailwind 4, i18next (en + km). No new dev dependencies required — Vitest is intentionally NOT added; the component is validated via feature tests + manual smoke checklist.

**Spec:** `docs/superpowers/specs/2026-06-27-cambodia-address-cascader-design.md`

---

## Conventions

- **Migrations** use anonymous-class return (`return new class extends Migration { … };`) following the existing project pattern. Timestamps in filenames come from `date +%Y_%m_%d_%H%M%S`.
- **Tests** use Laravel's PHPUnit + `RefreshDatabase` trait. Run individual tests with `php artisan test --filter=…`.
- **Locale** in API responses is determined by `Accept-Language` header (`en` or `km`); falls back to `en`.
- **Commits** use Conventional Commits (`feat:`, `test:`, `chore:`, `docs:`, `refactor:`). Commit at the end of every task.

---

## Phase 1 — Geographic Tables

### Task 1: Provinces migration + model

**Files:**
- Create: `database/migrations/<timestamp>_create_provinces_table.php`
- Create: `app/Models/Addresses/Province.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_provinces_table --create=provinces
```

Replace the generated file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name_en', 100);
            $table->string('name_km', 100);
            $table->enum('type', ['province', 'municipality'])->default('province');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['type', 'sort_order']);
            $table->index('name_en');
            $table->index('name_km');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

Expected: `Migrated: <timestamp>_create_provinces_table`.

- [ ] **Step 3: Create model**

Create `app/Models/Addresses/Province.php`:

```php
<?php

namespace App\Models\Addresses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = ['code', 'name_en', 'name_km', 'type', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'km' ? $this->name_km : $this->name_en;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_provinces_table.php app/Models/Addresses/Province.php
git commit -m "feat(addresses): provinces table and model"
```

---

### Task 2: Districts migration + model

**Files:**
- Create: `database/migrations/<timestamp>_create_districts_table.php`
- Create: `app/Models/Addresses/District.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_districts_table --create=districts
```

Replace the generated file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 15);
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('name_en', 100);
            $table->string('name_km', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['province_id', 'code']);
            $table->index('name_en');
            $table->index('name_km');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

Expected: `Migrated: <timestamp>_create_districts_table`.

- [ ] **Step 3: Create model**

Create `app/Models/Addresses/District.php`:

```php
<?php

namespace App\Models\Addresses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['code', 'province_id', 'name_en', 'name_km', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'km' ? $this->name_km : $this->name_en;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_districts_table.php app/Models/Addresses/District.php
git commit -m "feat(addresses): districts table and model"
```

---

### Task 3: Communes migration + model

**Files:**
- Create: `database/migrations/<timestamp>_create_communes_table.php`
- Create: `app/Models/Addresses/Commune.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_communes_table --create=communes
```

Replace the generated file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20);
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('name_en', 100);
            $table->string('name_km', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['district_id', 'code']);
            $table->index('name_en');
            $table->index('name_km');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communes');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Create model**

Create `app/Models/Addresses/Commune.php`:

```php
<?php

namespace App\Models\Addresses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commune extends Model
{
    protected $fillable = ['code', 'district_id', 'name_en', 'name_km', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'km' ? $this->name_km : $this->name_en;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_communes_table.php app/Models/Addresses/Commune.php
git commit -m "feat(addresses): communes table and model"
```

---

### Task 4: Villages migration + model

**Files:**
- Create: `database/migrations/<timestamp>_create_villages_table.php`
- Create: `app/Models/Addresses/Village.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_villages_table --create=villages
```

Replace the generated file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 25);
            $table->foreignId('commune_id')->constrained('communes')->cascadeOnDelete();
            $table->string('name_en', 100);
            $table->string('name_km', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['commune_id', 'code']);
            $table->index('name_en');
            $table->index('name_km');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Create model**

Create `app/Models/Addresses/Village.php`:

```php
<?php

namespace App\Models\Addresses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Village extends Model
{
    protected $fillable = ['code', 'commune_id', 'name_en', 'name_km', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'km' ? $this->name_km : $this->name_en;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_villages_table.php app/Models/Addresses/Village.php
git commit -m "feat(addresses): villages table and model"
```

---

### Task 5: Cambodia JSON dataset

**Files:**
- Create: `database/seeders/data/kh-addresses.json` (≈2 MB, ~16k rows)

- [ ] **Step 1: Source the dataset**

Download the ODC Cambodia administrative divisions dataset (CC-BY-SA, ODC):

```bash
mkdir -p database/seeders/data
# Fetch from Open Development Cambodia (data.opendevelopmentcambodia.net).
# If direct download is unavailable, copy the snapshot from
#   https://github.com/OpenDevelopmentCambodia/CSV-to-JSON-Converter (ODC datasets).
# Save to database/seeders/data/kh-addresses.json
```

If automated download fails, manually copy the JSON from a checked-in source. The shape must be:

```json
[
  {
    "p_code": "12",
    "p_type": "municipality",
    "p_name_en": "Phnom Penh",
    "p_name_km": "ភ្នំពេញ",
    "d_code": "1201",
    "d_name_en": "Doun Penh",
    "d_name_km": "ដូនពេញ",
    "c_code": "120101",
    "c_name_en": "Sangkat Wat Phnom",
    "c_name_km": "សង្កាត់វត្តភ្នំ",
    "v_code": "12010101",
    "v_name_en": "Phsar Kandal",
    "v_name_km": "ផ្សារកណ្តាល"
  }
]
```

One row per village; the seeder dedupes parent rows.

- [ ] **Step 2: Verify shape**

```bash
head -c 500 database/seeders/data/kh-addresses.json
```

Expected: Valid JSON array with the keys above.

- [ ] **Step 3: Verify counts**

```bash
python3 -c "import json; d=json.load(open('database/seeders/data/kh-addresses.json')); print('provinces:', len({r[\"p_code\"] for r in d})); print('districts:', len({(r[\"p_code\"], r[\"d_code\"]) for r in d})); print('communes:', len({(r[\"d_code\"], r[\"c_code\"]) for r in d})); print('villages:', len(d))"
```

Expected (approximate):
```
provinces: 25
districts: ~200
communes: ~1600
villages: ~14000
```

- [ ] **Step 4: Commit**

```bash
git add database/seeders/data/kh-addresses.json
git commit -m "chore(seed): Cambodia administrative divisions dataset"
```

---

### Task 6: Provinces seeder

**Files:**
- Create: `database/seeders/Addresses/ProvincesSeeder.php`

- [ ] **Step 1: Create seeder**

Create `database/seeders/Addresses/ProvincesSeeder.php`:

```php
<?php

namespace Database\Seeders\Addresses;

use App\Models\Addresses\Province;
use Illuminate\Database\Seeder;

class ProvincesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/kh-addresses.json');
        $rows = json_decode(file_get_contents($path), true);

        $provinces = [];
        foreach ($rows as $r) {
            $provinces[$r['p_code']] = [
                'code' => $r['p_code'],
                'name_en' => $r['p_name_en'],
                'name_km' => $r['p_name_km'],
                'type' => $r['p_type'] ?? 'province',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($provinces as $code => $data) {
            Province::updateOrCreate(['code' => $code], $data);
        }
    }
}
```

- [ ] **Step 2: Run seeder**

```bash
php artisan db:seed --class="Database\\Seeders\\Addresses\\ProvincesSeeder"
```

Expected: `Database seeded successfully.`

- [ ] **Step 3: Verify counts**

```bash
php artisan tinker --execute="echo App\\Models\\Addresses\\Province::count();"
```

Expected: `25` (or close).

- [ ] **Step 4: Commit**

```bash
git add database/seeders/Addresses/ProvincesSeeder.php
git commit -m "feat(seed): provinces seeder"
```

---

### Task 7: Districts seeder

**Files:**
- Create: `database/seeders/Addresses/DistrictsSeeder.php`

- [ ] **Step 1: Create seeder**

Create `database/seeders/Addresses/DistrictsSeeder.php`:

```php
<?php

namespace Database\Seeders\Addresses;

use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use Illuminate\Database\Seeder;

class DistrictsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('seeders/data/kh-addresses.json')), true);

        $districts = [];
        foreach ($rows as $r) {
            $province = Province::where('code', $r['p_code'])->first();
            if (!$province) continue;
            $key = $r['p_code'].'-'.$r['d_code'];
            $districts[$key] = [
                'province_id' => $province->id,
                'code' => $r['d_code'],
                'name_en' => $r['d_name_en'],
                'name_km' => $r['d_name_km'],
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($districts as $data) {
            District::updateOrCreate(
                ['province_id' => $data['province_id'], 'code' => $data['code']],
                $data
            );
        }
    }
}
```

- [ ] **Step 2: Run seeder**

```bash
php artisan db:seed --class="Database\\Seeders\\Addresses\\DistrictsSeeder"
```

- [ ] **Step 3: Verify counts**

```bash
php artisan tinker --execute="echo App\\Models\\Addresses\\District::count();"
```

Expected: `~200`.

- [ ] **Step 4: Commit**

```bash
git add database/seeders/Addresses/DistrictsSeeder.php
git commit -m "feat(seed): districts seeder"
```

---

### Task 8: Communes seeder

**Files:**
- Create: `database/seeders/Addresses/CommunesSeeder.php`

- [ ] **Step 1: Create seeder**

Create `database/seeders/Addresses/CommunesSeeder.php`:

```php
<?php

namespace Database\Seeders\Addresses;

use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use Illuminate\Database\Seeder;

class CommunesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('seeders/data/kh-addresses.json')), true);

        $communes = [];
        foreach ($rows as $r) {
            $district = District::where('code', $r['d_code'])->first();
            if (!$district) continue;
            $key = $r['d_code'].'-'.$r['c_code'];
            $communes[$key] = [
                'district_id' => $district->id,
                'code' => $r['c_code'],
                'name_en' => $r['c_name_en'],
                'name_km' => $r['c_name_km'],
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($communes as $data) {
            Commune::updateOrCreate(
                ['district_id' => $data['district_id'], 'code' => $data['code']],
                $data
            );
        }
    }
}
```

- [ ] **Step 2: Run seeder**

```bash
php artisan db:seed --class="Database\\Seeders\\Addresses\\CommunesSeeder"
```

- [ ] **Step 3: Verify counts**

```bash
php artisan tinker --execute="echo App\\Models\\Addresses\\Commune::count();"
```

Expected: `~1600`.

- [ ] **Step 4: Commit**

```bash
git add database/seeders/Addresses/CommunesSeeder.php
git commit -m "feat(seed): communes seeder"
```

---

### Task 9: Villages seeder + wire into DatabaseSeeder

**Files:**
- Create: `database/seeders/Addresses/VillagesSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create seeder**

Create `database/seeders/Addresses/VillagesSeeder.php`:

```php
<?php

namespace Database\Seeders\Addresses;

use App\Models\Addresses\Commune;
use App\Models\Addresses\Village;
use Illuminate\Database\Seeder;

class VillagesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('seeders/data/kh-addresses.json')), true);

        $now = now();
        $batch = [];
        $communeCache = [];

        foreach ($rows as $r) {
            $communeKey = $r['d_code'].'-'.$r['c_code'];
            if (!isset($communeCache[$communeKey])) {
                $communeCache[$communeKey] = Commune::where('code', $r['c_code'])->value('id');
            }
            $communeId = $communeCache[$communeKey];
            if (!$communeId) continue;

            $batch[] = [
                'commune_id' => $communeId,
                'code' => $r['v_code'],
                'name_en' => $r['v_name_en'],
                'name_km' => $r['v_name_km'],
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 500) {
                Village::upsert($batch, ['commune_id', 'code'], ['name_en', 'name_km', 'updated_at']);
                $batch = [];
            }
        }

        if ($batch) {
            Village::upsert($batch, ['commune_id', 'code'], ['name_en', 'name_km', 'updated_at']);
        }
    }
}
```

- [ ] **Step 2: Run seeder**

```bash
php artisan db:seed --class="Database\\Seeders\\Addresses\\VillagesSeeder"
```

- [ ] **Step 3: Verify counts**

```bash
php artisan tinker --execute="echo App\\Models\\Addresses\\Village::count();"
```

Expected: `~14000`.

- [ ] **Step 4: Update DatabaseSeeder**

Modify `database/seeders/DatabaseSeeder.php` `run()` to call the address seeders. Add inside the method:

```php
        $this->call([
            \Database\Seeders\Addresses\ProvincesSeeder::class,
            \Database\Seeders\Addresses\DistrictsSeeder::class,
            \Database\Seeders\Addresses\CommunesSeeder::class,
            \Database\Seeders\Addresses\VillagesSeeder::class,
        ]);
```

- [ ] **Step 5: Commit**

```bash
git add database/seeders/Addresses/VillagesSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat(seed): villages seeder and wire into DatabaseSeeder"
```

---

## Phase 2 — Address API

### Task 10: AddressController + provinces endpoint + feature test

**Files:**
- Create: `tests/Feature/AddressApiTest.php`
- Create: `app/Http/Controllers/Api/AddressController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AddressApiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Addresses\Province;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_provinces_endpoint_returns_localized_list(): void
    {
        Province::create(['code' => '12', 'name_en' => 'Phnom Penh', 'name_km' => 'ភ្នំពេញ', 'type' => 'municipality']);
        Province::create(['code' => '01', 'name_en' => 'Banteay Meanchey', 'name_km' => 'បន្ទាយមានឥស្លាម', 'type' => 'province']);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/addresses/provinces');

        $response->assertOk()
            ->assertJsonPath('data.0.label', 'Banteay Meanchey')
            ->assertJsonPath('data.1.label', 'Phnom Penh')
            ->assertJsonPath('data.0.label_km', 'បន្ទាយមានឥស្លាម');

        $response = $this->withHeaders(['Accept-Language' => 'km'])
            ->getJson('/api/addresses/provinces');

        $response->assertOk()
            ->assertJsonPath('data.0.label', 'បន្ទាយមានឥស្លាម');
    }

    public function test_provinces_endpoint_searches_by_khmer_name(): void
    {
        Province::create(['code' => '12', 'name_en' => 'Phnom Penh', 'name_km' => 'ភ្នំពេញ', 'type' => 'municipality']);
        Province::create(['code' => '01', 'name_en' => 'Banteay Meanchey', 'name_km' => 'បន្ទាយមានឥស្លាម', 'type' => 'province']);

        $response = $this->getJson('/api/addresses/provinces?q='.urlencode('ភ្នំ'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label_km', 'ភ្នំពេញ');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=AddressApiTest
```

Expected: FAIL with `404` (route not defined).

- [ ] **Step 3: Implement controller**

Create `app/Http/Controllers/Api/AddressController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use App\Models\Addresses\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AddressController extends Controller
{
    public function indexProvinces(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $cacheKey = 'addresses:provinces:'.$locale;

        $payload = Cache::remember($cacheKey, 86400, function () use ($locale) {
            $rows = Province::query()
                ->orderBy('type')
                ->orderBy($locale === 'km' ? 'name_km' : 'name_en')
                ->get();

            return $rows->map(fn ($p) => [
                'value' => $p->id,
                'code' => $p->code,
                'label' => $locale === 'km' ? $p->name_km : $p->name_en,
                'label_km' => $p->name_km,
            ])->all();
        });

        return response()->json(['data' => $payload]);
    }

    public function indexDistricts(Request $request, Province $province): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $rows = $this->filteredChildren($province->districts(), $request, $locale);

        return response()->json([
            'data' => $rows->map(fn ($d) => [
                'value' => $d->id,
                'code' => $d->code,
                'label' => $locale === 'km' ? $d->name_km : $d->name_en,
                'label_km' => $d->name_km,
            ])->all(),
        ]);
    }

    public function indexCommunes(Request $request, District $district): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $rows = $this->filteredChildren($district->communes(), $request, $locale);

        return response()->json([
            'data' => $rows->map(fn ($c) => [
                'value' => $c->id,
                'code' => $c->code,
                'label' => $locale === 'km' ? $c->name_km : $c->name_en,
                'label_km' => $c->name_km,
            ])->all(),
        ]);
    }

    public function indexVillages(Request $request, Commune $commune): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $rows = $this->filteredChildren($commune->villages(), $request, $locale);

        return response()->json([
            'data' => $rows->map(fn ($v) => [
                'value' => $v->id,
                'code' => $v->code,
                'label' => $locale === 'km' ? $v->name_km : $v->name_en,
                'label_km' => $v->name_km,
            ])->all(),
        ]);
    }

    private function filteredChildren($relation, Request $request, string $locale)
    {
        $q = $relation->newQuery();
        if ($search = $request->string('q')->toString()) {
            $q->where(function ($w) use ($search) {
                $w->where('name_en', 'like', '%'.$search.'%')
                  ->orWhere('name_km', 'like', '%'.$search.'%');
            });
        }
        return $q->orderBy($locale === 'km' ? 'name_km' : 'name_en')->get();
    }

    private function resolveLocale(Request $request): string
    {
        $al = $request->header('Accept-Language', 'en');
        return str_starts_with($al, 'km') ? 'km' : 'en';
    }
}
```

- [ ] **Step 4: Register routes**

Edit `routes/api.php`. Add at the bottom:

```php
// Cambodia address cascader (public — geographic reference data)
Route::prefix('addresses')->group(function () {
    Route::get('provinces', [\App\Http\Controllers\Api\AddressController::class, 'indexProvinces']);
    Route::get('provinces/{province}/districts', [\App\Http\Controllers\Api\AddressController::class, 'indexDistricts']);
    Route::get('districts/{district}/communes', [\App\Http\Controllers\Api\AddressController::class, 'indexCommunes']);
    Route::get('communes/{commune}/villages', [\App\Http\Controllers\Api\AddressController::class, 'indexVillages']);
});
```

- [ ] **Step 5: Run test to verify it passes**

```bash
php artisan test --filter=AddressApiTest
```

Expected: PASS (both tests).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/AddressController.php routes/api.php tests/Feature/AddressApiTest.php
git commit -m "feat(addresses): provinces API with locale + search"
```

---

### Task 11: Districts endpoint feature test

**Files:**
- Modify: `tests/Feature/AddressApiTest.php`

- [ ] **Step 1: Add test**

Append to `tests/Feature/AddressApiTest.php`:

```php
    public function test_districts_endpoint_returns_children_of_province(): void
    {
        $pp = Province::create(['code' => '12', 'name_en' => 'Phnom Penh', 'name_km' => 'ភ្នំពេញ', 'type' => 'municipality']);
        $bmc = Province::create(['code' => '01', 'name_en' => 'Banteay Meanchey', 'name_km' => 'បន្ទាយមានឥស្លាម', 'type' => 'province']);

        \App\Models\Addresses\District::create(['province_id' => $pp->id, 'code' => '1201', 'name_en' => 'Doun Penh', 'name_km' => 'ដូនពេញ']);
        \App\Models\Addresses\District::create(['province_id' => $bmc->id, 'code' => '0101', 'name_en' => 'Mongkol Borei', 'name_km' => 'មង្គលបូរី']);

        $response = $this->getJson("/api/addresses/provinces/{$pp->id}/districts");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Doun Penh');
    }
```

- [ ] **Step 2: Run test**

```bash
php artisan test --filter=AddressApiTest::test_districts_endpoint_returns_children_of_province
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/AddressApiTest.php
git commit -m "test(addresses): districts endpoint coverage"
```

---

### Task 12: Communes + Villages endpoint feature tests

**Files:**
- Modify: `tests/Feature/AddressApiTest.php`

- [ ] **Step 1: Add tests**

Append to `tests/Feature/AddressApiTest.php`:

```php
    public function test_communes_endpoint_returns_children_of_district(): void
    {
        $pp = Province::create(['code' => '12', 'name_en' => 'Phnom Penh', 'name_km' => 'ភ្នំពេញ', 'type' => 'municipality']);
        $d = \App\Models\Addresses\District::create(['province_id' => $pp->id, 'code' => '1201', 'name_en' => 'Doun Penh', 'name_km' => 'ដូនពេញ']);
        $other = \App\Models\Addresses\District::create(['province_id' => $pp->id, 'code' => '1202', 'name_en' => 'Other', 'name_km' => 'ផ្សេង']);

        \App\Models\Addresses\Commune::create(['district_id' => $d->id, 'code' => '120101', 'name_en' => 'Wat Phnom', 'name_km' => 'វត្តភ្នំ']);
        \App\Models\Addresses\Commune::create(['district_id' => $other->id, 'code' => '120201', 'name_en' => 'Other Commune', 'name_km' => 'ឃុំផ្សេង']);

        $response = $this->getJson("/api/addresses/districts/{$d->id}/communes");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Wat Phnom');
    }

    public function test_villages_endpoint_returns_children_of_commune(): void
    {
        $pp = Province::create(['code' => '12', 'name_en' => 'Phnom Penh', 'name_km' => 'ភ្នំពេញ', 'type' => 'municipality']);
        $d = \App\Models\Addresses\District::create(['province_id' => $pp->id, 'code' => '1201', 'name_en' => 'Doun Penh', 'name_km' => 'ដូនពេញ']);
        $c = \App\Models\Addresses\Commune::create(['district_id' => $d->id, 'code' => '120101', 'name_en' => 'Wat Phnom', 'name_km' => 'វត្តភ្នំ']);
        $other = \App\Models\Addresses\Commune::create(['district_id' => $d->id, 'code' => '120102', 'name_en' => 'Other Commune', 'name_km' => 'ផ្សេង']);

        \App\Models\Addresses\Village::create(['commune_id' => $c->id, 'code' => '12010101', 'name_en' => 'Phsar Kandal', 'name_km' => 'ផ្សារកណ្តាល']);
        \App\Models\Addresses\Village::create(['commune_id' => $other->id, 'code' => '12010201', 'name_en' => 'Other Village', 'name_km' => 'ភូមិផ្សេង']);

        $response = $this->getJson("/api/addresses/communes/{$c->id}/villages");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Phsar Kandal');
    }
```

- [ ] **Step 2: Run all address tests**

```bash
php artisan test --filter=AddressApiTest
```

Expected: All 4 tests PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/AddressApiTest.php
git commit -m "test(addresses): communes and villages endpoint coverage"
```

---

## Phase 3 — Existing Entity FK Migrations

### Task 13: Customer FK migration + model relationships

**Files:**
- Create: `database/migrations/<timestamp>_add_address_fks_to_customers_table.php`
- Modify: `app/Models/Customer.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration add_address_fks_to_customers_table --table=customers
```

Replace the generated file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('address')->constrained('provinces')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('province_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->after('district_id')->constrained('communes')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('commune_id')->constrained('villages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['commune_id']);
            $table->dropForeign(['village_id']);
            $table->dropColumn(['province_id', 'district_id', 'commune_id', 'village_id']);
        });
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Update Customer model**

Replace `app/Models/Customer.php` with:

```php
<?php

namespace App\Models;

use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use App\Models\Addresses\Village;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'contact_person', 'email', 'phone', 'address', 'province_id', 'district_id', 'commune_id', 'village_id', 'type', 'credit_limit', 'current_balance', 'payment_terms', 'notes', 'is_active'])]
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ];
    }

    protected function getTypeAttribute(): string
    {
        return $this->attributes['type'] ?? 'retail';
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_add_address_fks_to_customers_table.php app/Models/Customer.php
git commit -m "feat(customer): address FK columns and relationships"
```

---

### Task 14: Supplier FK migration + model relationships

**Files:**
- Create: `database/migrations/<timestamp>_add_address_fks_to_suppliers_table.php`
- Modify: `app/Models/Supplier.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration add_address_fks_to_suppliers_table --table=suppliers
```

Replace the generated file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('address')->constrained('provinces')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('province_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->after('district_id')->constrained('communes')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('commune_id')->constrained('villages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['commune_id']);
            $table->dropForeign(['village_id']);
            $table->dropColumn(['province_id', 'district_id', 'commune_id', 'village_id']);
        });
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Update Supplier model**

Replace `app/Models/Supplier.php` with:

```php
<?php

namespace App\Models;

use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use App\Models\Addresses\Village;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'contact_person', 'email', 'phone', 'address', 'province_id', 'district_id', 'commune_id', 'village_id', 'tax_number', 'payment_terms', 'notes', 'is_active'])]
class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_add_address_fks_to_suppliers_table.php app/Models/Supplier.php
git commit -m "feat(supplier): address FK columns and relationships"
```

---

### Task 15: Warehouse FK migration + model relationships

**Files:**
- Create: `database/migrations/<timestamp>_add_address_fks_to_warehouses_table.php`
- Modify: `app/Models/Warehouse.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration add_address_fks_to_warehouses_table --table=warehouses
```

Replace the generated file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('address')->constrained('provinces')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('province_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->after('district_id')->constrained('communes')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('commune_id')->constrained('villages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['commune_id']);
            $table->dropForeign(['village_id']);
            $table->dropColumn(['province_id', 'district_id', 'commune_id', 'village_id']);
        });
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Update Warehouse model**

Replace `app/Models/Warehouse.php` with:

```php
<?php

namespace App\Models;

use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use App\Models\Addresses\Village;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'code', 'address', 'province_id', 'district_id', 'commune_id', 'village_id', 'phone', 'is_default', 'is_active'])]
class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Warehouse $warehouse) {
            if ($warehouse->is_default) {
                DB::transaction(function () use ($warehouse) {
                    static::where('id', '!=', $warehouse->id ?? 0)->update(['is_default' => false]);
                });
            }
        });
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_add_address_fks_to_warehouses_table.php app/Models/Warehouse.php
git commit -m "feat(warehouse): address FK columns and relationships"
```

---

### Task 16: Update Customer request validation + resource

**Files:**
- Modify: `app/Http/Requests/StoreCustomerRequest.php`
- Modify: `app/Http/Requests/UpdateCustomerRequest.php`
- Modify: `app/Http/Resources/CustomerResource.php`
- Modify: `app/Http/Controllers/Api/CustomerController.php`
- Create: `tests/Feature/CustomerAddressTest.php`

- [ ] **Step 1: Update StoreCustomerRequest**

Add to the `rules()` array:

```php
            'address'      => 'nullable|string|max:500',
            'province_id'  => 'nullable|exists:provinces,id',
            'district_id'  => 'nullable|exists:districts,id',
            'commune_id'   => 'nullable|exists:communes,id',
            'village_id'   => 'nullable|exists:villages,id',
```

- [ ] **Step 2: Update UpdateCustomerRequest**

Same four lines added to `rules()`.

- [ ] **Step 3: Update CustomerResource**

Modify `toArray($request)` to merge:

```php
        return array_merge(parent::toArray($request), [
            'province' => $this->whenLoaded('province', fn () => [
                'id' => $this->province->id,
                'code' => $this->province->code,
                'name' => $this->province->name,
                'name_km' => $this->province->name_km,
            ]),
            'district' => $this->whenLoaded('district', fn () => [
                'id' => $this->district->id,
                'code' => $this->district->code,
                'name' => $this->district->name,
                'name_km' => $this->district->name_km,
            ]),
            'commune' => $this->whenLoaded('commune', fn () => [
                'id' => $this->commune->id,
                'code' => $this->commune->code,
                'name' => $this->commune->name,
                'name_km' => $this->commune->name_km,
            ]),
            'village' => $this->whenLoaded('village', fn () => [
                'id' => $this->village->id,
                'code' => $this->village->code,
                'name' => $this->village->name,
                'name_km' => $this->village->name_km,
            ]),
        ]);
```

- [ ] **Step 4: Update CustomerController**

In `index()`, before `paginate(15)`:
```php
$query->with(['province', 'district', 'commune', 'village']);
```

In `show()`:
```php
return new CustomerResource($customer->load(['province', 'district', 'commune', 'village']));
```

In `store()` and `update()`, before returning:
```php
$customer->loadMissing(['province', 'district', 'commune', 'village']);
```

- [ ] **Step 5: Write customer address feature test**

Create `tests/Feature/CustomerAddressTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use App\Models\Addresses\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_customer_with_address_fks(): void
    {
        $user = User::factory()->create();
        $p = Province::create(['code' => '12', 'name_en' => 'Phnom Penh', 'name_km' => 'ភ្នំពេញ', 'type' => 'municipality']);
        $d = District::create(['province_id' => $p->id, 'code' => '1201', 'name_en' => 'Doun Penh', 'name_km' => 'ដូនពេញ']);
        $c = Commune::create(['district_id' => $d->id, 'code' => '120101', 'name_en' => 'Wat Phnom', 'name_km' => 'វត្តភ្នំ']);
        $v = Village::create(['commune_id' => $c->id, 'code' => '12010101', 'name_en' => 'Phsar Kandal', 'name_km' => 'ផ្សារកណ្តាល']);

        $payload = [
            'name' => 'Test Co',
            'type' => 'retail',
            'address' => '#123, St. 271',
            'province_id' => $p->id,
            'district_id' => $d->id,
            'commune_id'  => $c->id,
            'village_id'  => $v->id,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/customers', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('customers', [
            'name' => 'Test Co',
            'province_id' => $p->id,
            'district_id' => $d->id,
            'commune_id'  => $c->id,
            'village_id'  => $v->id,
            'address' => '#123, St. 271',
        ]);
    }

    public function test_validation_rejects_nonexistent_fk(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/customers', [
                'name' => 'Bad',
                'province_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['province_id']);
    }
}
```

- [ ] **Step 6: Run tests**

```bash
php artisan test --filter=CustomerAddressTest
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/StoreCustomerRequest.php app/Http/Requests/UpdateCustomerRequest.php app/Http/Resources/CustomerResource.php app/Http/Controllers/Api/CustomerController.php tests/Feature/CustomerAddressTest.php
git commit -m "feat(customer): validate and return address FKs"
```

---

### Task 17: Update Supplier request validation + resource

**Files:**
- Modify: `app/Http/Requests/StoreSupplierRequest.php`
- Modify: `app/Http/Requests/UpdateSupplierRequest.php`
- Modify: `app/Http/Resources/SupplierResource.php`
- Modify: `app/Http/Controllers/Api/SupplierController.php`
- Create: `tests/Feature/SupplierAddressTest.php`

- [ ] **Step 1: Update Store/Update SupplierRequest**

Add the same four lines:

```php
            'address'      => 'nullable|string|max:500',
            'province_id'  => 'nullable|exists:provinces,id',
            'district_id'  => 'nullable|exists:districts,id',
            'commune_id'   => 'nullable|exists:communes,id',
            'village_id'   => 'nullable|exists:villages,id',
```

- [ ] **Step 2: Update SupplierResource**

Mirror the `whenLoaded` additions from Task 16 for the four relations.

- [ ] **Step 3: Update SupplierController**

In `index()`, before `paginate(15)`:
```php
$query->with(['province', 'district', 'commune', 'village']);
```

In `show()`:
```php
return new SupplierResource($supplier->load(['province', 'district', 'commune', 'village']));
```

In `store()` / `update()`, before returning:
```php
$supplier->loadMissing(['province', 'district', 'commune', 'village']);
```

- [ ] **Step 4: Write supplier address test**

Create `tests/Feature/SupplierAddressTest.php` (same shape as `CustomerAddressTest` but POSTing to `/api/suppliers` and asserting on the `suppliers` table).

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter=SupplierAddressTest
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/StoreSupplierRequest.php app/Http/Requests/UpdateSupplierRequest.php app/Http/Resources/SupplierResource.php app/Http/Controllers/Api/SupplierController.php tests/Feature/SupplierAddressTest.php
git commit -m "feat(supplier): validate and return address FKs"
```

---

### Task 18: Update Warehouse request validation + resource

**Files:**
- Modify: `app/Http/Requests/StoreWarehouseRequest.php`
- Modify: `app/Http/Requests/UpdateWarehouseRequest.php`
- Modify: `app/Http/Resources/WarehouseResource.php`
- Modify: `app/Http/Controllers/Api/WarehouseController.php`
- Create: `tests/Feature/WarehouseAddressTest.php`

- [ ] **Step 1: Update Store/Update WarehouseRequest**

Add the same four lines:
```php
            'address'      => 'nullable|string|max:500',
            'province_id'  => 'nullable|exists:provinces,id',
            'district_id'  => 'nullable|exists:districts,id',
            'commune_id'   => 'nullable|exists:communes,id',
            'village_id'   => 'nullable|exists:villages,id',
```

- [ ] **Step 2: Update WarehouseResource**

Same `whenLoaded` additions.

- [ ] **Step 3: Update WarehouseController**

In `index()`, before `paginate(15)`:
```php
$query->with(['province', 'district', 'commune', 'village']);
```

In `show()`:
```php
return new WarehouseResource($warehouse->load(['province', 'district', 'commune', 'village']));
```

In `store()` / `update()`, before returning:
```php
$warehouse->loadMissing(['province', 'district', 'commune', 'village']);
```

- [ ] **Step 4: Write warehouse address test**

Create `tests/Feature/WarehouseAddressTest.php` (same shape, POSTing to `/api/warehouses`).

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter=WarehouseAddressTest
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/StoreWarehouseRequest.php app/Http/Requests/UpdateWarehouseRequest.php app/Http/Resources/WarehouseResource.php app/Http/Controllers/Api/WarehouseController.php tests/Feature/WarehouseAddressTest.php
git commit -m "feat(warehouse): validate and return address FKs"
```

---

## Phase 4 — Frontend

### Task 19: Addresses Pinia store

**Files:**
- Create: `resources/js/stores/addresses.js`

- [ ] **Step 1: Create store**

Create `resources/js/stores/addresses.js`:

```js
import { defineStore } from 'pinia';
import axios from 'axios';
import { ref } from 'vue';

export const useAddressesStore = defineStore('addresses', () => {
    const provinces = ref([]);
    const provincesLoaded = ref(false);
    const childCache = ref({});

    async function loadProvinces(force = false) {
        if (provincesLoaded.value && !force) return provinces.value;
        const { data } = await axios.get('/api/addresses/provinces');
        provinces.value = data.data;
        provincesLoaded.value = true;
        return provinces.value;
    }

    async function loadDistricts(provinceId, search = '') {
        return loadChildren(`provinces:${provinceId}`, `/api/addresses/provinces/${provinceId}/districts`, search);
    }

    async function loadCommunes(districtId, search = '') {
        return loadChildren(`districts:${districtId}`, `/api/addresses/districts/${districtId}/communes`, search);
    }

    async function loadVillages(communeId, search = '') {
        return loadChildren(`communes:${communeId}`, `/api/addresses/communes/${communeId}/villages`, search);
    }

    async function loadChildren(key, url, search) {
        if (!childCache.value[key]) {
            const { data } = await axios.get(url);
            childCache.value[key] = data.data;
        }
        if (!search) return childCache.value[key];
        const s = search.toLowerCase();
        return childCache.value[key].filter(
            (item) => item.label.toLowerCase().includes(s) || (item.label_km || '').includes(search)
        );
    }

    function resetChildren(...keys) {
        for (const k of keys) delete childCache.value[k];
    }

    return {
        provinces, childCache,
        loadProvinces, loadDistricts, loadCommunes, loadVillages, resetChildren,
    };
});
```

- [ ] **Step 2: Verify it builds**

```bash
npm run build
```

Expected: build succeeds.

- [ ] **Step 3: Commit**

```bash
git add resources/js/stores/addresses.js
git commit -m "feat(addresses): Pinia store for cascading lookups"
```

---

### Task 20: AddressCascader.vue component

**Files:**
- Create: `resources/js/components/AddressCascader.vue`

- [ ] **Step 1: Create component**

Create `resources/js/components/AddressCascader.vue`:

```vue
<template>
    <div class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <BaseSelect
                v-model="local.province_id"
                name="province_id"
                label="Province"
                :options="provinceOptions"
                :required="required.province"
                :error="errors?.province_id"
                placeholder="Select province"
                @update:modelValue="onProvinceChange"
            />
            <BaseSelect
                v-model="local.district_id"
                name="district_id"
                label="District"
                :options="districtOptions"
                :required="required.district"
                :error="errors?.district_id"
                :disabled="!local.province_id || loadingDistricts"
                placeholder="Select district"
                @update:modelValue="onDistrictChange"
            />
            <BaseSelect
                v-model="local.commune_id"
                name="commune_id"
                label="Commune"
                :options="filteredCommunes"
                :required="required.commune"
                :error="errors?.commune_id"
                :disabled="!local.district_id || loadingCommunes"
                placeholder="Select commune"
                @update:modelValue="onCommuneChange"
            />
            <BaseSelect
                v-model="local.village_id"
                name="village_id"
                label="Village"
                :options="filteredVillages"
                :required="required.village"
                :error="errors?.village_id"
                :disabled="!local.commune_id || loadingVillages"
                placeholder="Select village"
            />
        </div>

        <div v-if="communes.length >= 30">
            <BaseInput v-model="communeSearch" label="Search communes" placeholder="Type to filter" />
        </div>
        <div v-if="villages.length >= 30">
            <BaseInput v-model="villageSearch" label="Search villages" placeholder="Type to filter" />
        </div>

        <div v-if="showAddressField">
            <label :for="addressId" class="block text-sm font-medium text-slate-700 mb-1">Street / House no.</label>
            <textarea
                :id="addressId"
                v-model="local.address"
                name="address"
                rows="2"
                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                :placeholder="addressPlaceholder"
            />
            <p v-if="errors?.address" class="mt-1 text-xs text-rose-600">{{ errors.address }}</p>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import { useAddressesStore } from '@/stores/addresses';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
        default: () => ({
            province_id: null, district_id: null, commune_id: null,
            village_id: null, address: '',
        }),
    },
    required: {
        type: Object,
        default: () => ({ province: true, district: true, commune: false, village: false }),
    },
    errors: { type: Object, default: () => ({}) },
    showAddressField: { type: Boolean, default: true },
    addressPlaceholder: { type: String, default: 'Street, house number, landmark' },
});

const emit = defineEmits(['update:modelValue']);

const store = useAddressesStore();
const addressId = `addr-${Math.random().toString(36).slice(2, 9)}`;

const local = reactive({
    province_id: props.modelValue.province_id ?? null,
    district_id: props.modelValue.district_id ?? null,
    commune_id: props.modelValue.commune_id ?? null,
    village_id: props.modelValue.village_id ?? null,
    address: props.modelValue.address ?? '',
});

const provinceOptions = computed(() => store.provinces.map((p) => ({ value: p.value, label: p.label })));

const districts = ref([]);
const communes = ref([]);
const villages = ref([]);

const loadingDistricts = ref(false);
const loadingCommunes = ref(false);
const loadingVillages = ref(false);

const communeSearch = ref('');
const villageSearch = ref('');

const districtOptions = computed(() => districts.value.map((d) => ({ value: d.value, label: d.label })));
const filteredCommunes = computed(() => {
    const s = communeSearch.value.toLowerCase();
    return communes.value
        .filter((c) => !s || c.label.toLowerCase().includes(s) || (c.label_km || '').includes(communeSearch.value))
        .map((c) => ({ value: c.value, label: c.label }));
});
const filteredVillages = computed(() => {
    const s = villageSearch.value.toLowerCase();
    return villages.value
        .filter((v) => !s || v.label.toLowerCase().includes(s) || (v.label_km || '').includes(villageSearch.value))
        .map((v) => ({ value: v.value, label: v.label }));
});

async function loadForCurrentState() {
    if (local.province_id) {
        districts.value = await store.loadDistricts(local.province_id);
        if (local.district_id) {
            communes.value = await store.loadCommunes(local.district_id);
            if (local.commune_id) {
                villages.value = await store.loadVillages(local.commune_id);
            } else {
                villages.value = [];
            }
        } else {
            communes.value = [];
            villages.value = [];
        }
    } else {
        districts.value = [];
        communes.value = [];
        villages.value = [];
    }
}

async function onProvinceChange(value) {
    local.district_id = null;
    local.commune_id = null;
    local.village_id = null;
    communes.value = [];
    villages.value = [];
    if (!value) {
        districts.value = [];
        emitUpdate();
        return;
    }
    loadingDistricts.value = true;
    try {
        districts.value = await store.loadDistricts(value);
    } finally {
        loadingDistricts.value = false;
    }
    emitUpdate();
}

async function onDistrictChange(value) {
    local.commune_id = null;
    local.village_id = null;
    villages.value = [];
    if (!value) {
        communes.value = [];
        emitUpdate();
        return;
    }
    loadingCommunes.value = true;
    try {
        communes.value = await store.loadCommunes(value);
    } finally {
        loadingCommunes.value = false;
    }
    emitUpdate();
}

async function onCommuneChange(value) {
    local.village_id = null;
    if (!value) {
        villages.value = [];
        emitUpdate();
        return;
    }
    loadingVillages.value = true;
    try {
        villages.value = await store.loadVillages(value);
    } finally {
        loadingVillages.value = false;
    }
    emitUpdate();
}

watch(() => local.address, emitUpdate);
watch(() => local.village_id, (nv) => { local.village_id = nv; emitUpdate(); });

function emitUpdate() {
    emit('update:modelValue', { ...local });
}

watch(
    () => props.modelValue,
    (nv) => {
        if (!nv) return;
        if (nv.province_id !== local.province_id
            || nv.district_id !== local.district_id
            || nv.commune_id !== local.commune_id
            || nv.village_id !== local.village_id) {
            local.province_id = nv.province_id ?? null;
            local.district_id = nv.district_id ?? null;
            local.commune_id = nv.commune_id ?? null;
            local.village_id = nv.village_id ?? null;
            local.address = nv.address ?? '';
            loadForCurrentState();
        }
    },
    { deep: true }
);

onMounted(async () => {
    await store.loadProvinces();
    await loadForCurrentState();
});
</script>
```

- [ ] **Step 2: Verify it builds**

```bash
npm run build
```

Expected: build succeeds.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/AddressCascader.vue
git commit -m "feat(addresses): AddressCascader Vue component"
```

---

### Task 21: Integrate AddressCascader into CustomerForm

**Files:**
- Modify: `resources/js/pages/master/CustomerForm.vue`

- [ ] **Step 1: Add import**

Add to `<script setup>` imports:
```js
import AddressCascader from '@/components/AddressCascader.vue';
```

- [ ] **Step 2: Extend form reactive object**

Change the `form` reactive to:
```js
const form = reactive({
    id: null, code: '', name: '', contact_person: '', email: '', phone: '',
    addresses: { province_id: null, district_id: null, commune_id: null, village_id: null, address: '' },
    type: 'retail', credit_limit: 0, current_balance: 0,
    payment_terms: '', notes: '', is_active: true,
});
```

- [ ] **Step 3: Replace address textarea**

Find the `<textarea id="customer-address" v-model="form.address" ...>` block and replace with:
```vue
                    <div class="md:col-span-2">
                        <AddressCascader
                            v-model="form.addresses"
                            :required="{ province: true, district: true, commune: true }"
                            :errors="errors"
                        />
                    </div>
```

- [ ] **Step 4: Update save() payload**

In `save()`, replace the payload construction:
```js
        const payload = {
            ...form,
            province_id: form.addresses.province_id,
            district_id: form.addresses.district_id,
            commune_id: form.addresses.commune_id,
            village_id: form.addresses.village_id,
            address: form.addresses.address,
        };
        delete payload.addresses;
```

- [ ] **Step 5: Update loader in onMounted**

When loading an existing customer, replace `address: data.address || ''` in the `Object.assign(form, …)` call with:
```js
            addresses: {
                province_id: data.province_id ?? null,
                district_id: data.district_id ?? null,
                commune_id:  data.commune_id ?? null,
                village_id:  data.village_id ?? null,
                address:     data.address ?? '',
            },
```

- [ ] **Step 6: Build**

```bash
npm run build
```

Expected: build succeeds.

- [ ] **Step 7: Commit**

```bash
git add resources/js/pages/master/CustomerForm.vue
git commit -m "feat(customer-form): integrate AddressCascader"
```

---

### Task 22: Integrate AddressCascader into SupplierForm

**Files:**
- Modify: `resources/js/pages/master/SupplierForm.vue`

- [ ] **Step 1: Repeat the same edits**

Mirror Task 21:
- Import `AddressCascader`.
- Add `addresses` object to `form`.
- Replace address textarea.
- Update `save()` payload.
- Update loader.

Use `required` config: `{ province: true, district: true }`.

- [ ] **Step 2: Build**

```bash
npm run build
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/master/SupplierForm.vue
git commit -m "feat(supplier-form): integrate AddressCascader"
```

---

### Task 23: Integrate AddressCascader into WarehouseForm

**Files:**
- Modify: `resources/js/pages/master/WarehouseForm.vue` (or the closest equivalent)

- [ ] **Step 1: Apply same edits as Task 21**

Same pattern. `required` config: `{ province: true, district: true }`.

- [ ] **Step 2: Build**

```bash
npm run build
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/master/WarehouseForm.vue
git commit -m "feat(warehouse-form): integrate AddressCascader"
```

---

### Task 24: Format address in Customers list view

**Files:**
- Modify: `resources/js/pages/master/Customers.vue`

- [ ] **Step 1: Find the address column**

Locate the column that currently renders `customer.address`.

- [ ] **Step 2: Replace with formatted address**

Change `{{ customer.address }}` (or equivalent) to `{{ formatAddress(customer) }}`.

Add a helper in `<script setup>`:
```js
function formatAddress(c) {
    const parts = [];
    if (c.address) parts.push(c.address);
    if (c.village?.name) parts.push(c.village.name);
    if (c.commune?.name) parts.push(c.commune.name);
    if (c.district?.name) parts.push(c.district.name);
    if (c.province?.name) parts.push(c.province.name);
    return parts.length ? parts.join(' • ') : (c.address || '—');
}
```

- [ ] **Step 3: Build**

```bash
npm run build
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/master/Customers.vue
git commit -m "feat(customers-list): formatted hierarchical address"
```

---

### Task 25: Format address in Suppliers list view

**Files:**
- Modify: `resources/js/pages/master/Suppliers.vue`

- [ ] **Step 1: Apply same change as Task 24**

Add the same `formatAddress` helper and use it in place of the existing address column.

- [ ] **Step 2: Build + commit**

```bash
npm run build
git add resources/js/pages/master/Suppliers.vue
git commit -m "feat(suppliers-list): formatted hierarchical address"
```

---

### Task 26: Format address in Warehouses list view

**Files:**
- Modify: `resources/js/pages/master/Warehouses.vue`

- [ ] **Step 1: Apply same change as Task 24**

Same `formatAddress` helper.

- [ ] **Step 2: Build + commit**

```bash
npm run build
git add resources/js/pages/master/Warehouses.vue
git commit -m "feat(warehouses-list): formatted hierarchical address"
```

---

## Phase 5 — Validation

### Task 27: Run full test suite + manual smoke checklist

- [ ] **Step 1: Run full PHP test suite**

```bash
php artisan test
```

Expected: All tests PASS (existing + 9 new feature tests).

- [ ] **Step 2: Run frontend build**

```bash
npm run build
```

Expected: Build succeeds.

- [ ] **Step 3: Manual smoke — Customer form**

Start dev servers:
```bash
php artisan serve
npm run dev
```

1. Log in, navigate to Customers → Create customer.
2. Verify the four cascading dropdowns render with Khmer + English names.
3. Select Phnom Penh → confirm districts load.
4. Select a district → confirm communes load with typeahead.
5. Select a commune → confirm villages load with typeahead.
6. Type a street address, save.
7. Re-open the customer → verify all four selectors and the text address restore.

- [ ] **Step 4: Manual smoke — Supplier form**

Same flow. Verify with only `province_id` + `district_id` set (no commune/village) saves successfully.

- [ ] **Step 5: Manual smoke — Warehouse form**

Same flow. Edit a legacy warehouse that has only `address` text. Confirm selectors load empty and can be augmented.

- [ ] **Step 6: Manual smoke — Locale switching**

Switch UI to Khmer (top-right language switcher). Open the Customer form. Confirm dropdown labels show Khmer names.

- [ ] **Step 7: Manual smoke — API caching**

```bash
php artisan tinker --execute="echo Cache::has('addresses:provinces:en');"
```

Expected: `1` (after the first API call).

- [ ] **Step 8: Tag the milestone**

```bash
git tag -a feat/cambodia-address-cascader -m "Cambodia address cascader for customer/supplier/warehouse"
```

---

## Phase 6 — Outside-Cambodia Amendment

> **Added 2026-06-28** per `.kimchi/docs/drafts/cambodia-address-cascader-amendment.md`. This phase is optional — implement only if/when the outside-Cambodia use case is needed. Each task here is independent and may run after Phase 5 ships.

### Task 28: Add `country` + `address_line2` migrations (3 tables)

**Files:**
- Create: `database/migrations/<timestamp>_add_country_and_address_line2_to_customers_table.php`
- Create: `database/migrations/<timestamp>_add_country_and_address_line2_to_suppliers_table.php`
- Create: `database/migrations/<timestamp>_add_country_and_address_line2_to_warehouses_table.php`

- [ ] **Step 1: Generate the three migrations**

```bash
php artisan make:migration add_country_and_address_line2_to_customers_table --table=customers
php artisan make:migration add_country_and_address_line2_to_suppliers_table --table=suppliers
php artisan make:migration add_country_and_address_line2_to_warehouses_table --table=warehouses
```

- [ ] **Step 2: Replace each migration file**

Use the body from `.kimchi/docs/drafts/cambodia-address-cascader-amendment.md` Section 2.1. Each migration adds two nullable columns:

```php
Schema::table('<table>', function (Blueprint $table) {
    $table->string('country', 100)->nullable()->after('address');
    $table->string('address_line2', 500)->nullable()->after('address');
});
```

The `down()` method drops both columns.

- [ ] **Step 3: Run migrations**

```bash
php artisan migrate
```

Expected: three new migrations applied. Verify with `php artisan migrate:status`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_add_country_and_address_line2_to_*.php
git commit -m "feat(addresses): country + address_line2 columns on customers, suppliers, warehouses"
```

---

### Task 29: AddressCascader.vue — outside-Cambodia toggle UI

**Files:**
- Modify: `resources/js/components/AddressCascader.vue`
- Modify (if needed): `resources/js/stores/addresses.js` — no change required; the toggle is local-state only

- [ ] **Step 1: Extend props and emitted payload**

Replace the Props block with the amended version from `.kimchi/docs/drafts/cambodia-address-cascader-amendment.md` Section 3.1. Add:

- `country: string` to `modelValue` (default `''`)
- `address_line2?: string` to `modelValue`
- `showCountryToggle?: boolean` prop (default `true`)

Emitted shape becomes:

```ts
{
  country: string,
  province_id: number|null, district_id: number|null,
  commune_id: number|null,  village_id: number|null,
  address: string,
  address_line2: string
}
```

- [ ] **Step 2: Add the toggle checkbox + conditional rendering**

Above the 4 cascading selects, render a checkbox labeled "Address is outside Cambodia". When checked:

- Hide the 4 selects (and their `BaseSelect` instances).
- Show 4 free-text inputs: Country, City, Street address, Address line 2.
- Country and Address line 2 bind directly to `country` and `address_line2`. City is folded into `address` on the `@input` event (e.g. `${street}, ${city}` when city present, else just `${street}`).
- Inline note: "Non-Cambodia addresses are stored as free-text."

When unchecked: restore the cascader. Clear `country` and `address_line2`; preserve `province_id`/`district_id`/`commune_id`/`village_id` if previously set.

Initial mode is derived from `modelValue.country` (non-empty → outside-Cambodia, empty → Cambodia).

- [ ] **Step 3: Build verification**

```bash
npm run build
```

Expected: build succeeds with no new warnings.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/AddressCascader.vue
git commit -m "feat(address-cascader): outside-Cambodia toggle and free-text mode"
```

---

### Task 30: CustomerForm — extend form model + save payload + resource/model

**Files:**
- Modify: `resources/js/pages/master/CustomerForm.vue`
- Modify: `resources/js/stores/customers.js`
- Modify: `app/Models/Customer.php` (extend `#[Fillable(...)]` to include `country` and `address_line2`)
- Modify: `app/Http/Resources/CustomerResource.php` (expose `country` and `address_line2`)
- Modify: `app/Http/Requests/StoreCustomerRequest.php`, `UpdateCustomerRequest.php` (extend rules + add `withValidator` conditional)

- [ ] **Step 1: Extend `form.addresses` in CustomerForm.vue**

```js
addresses: {
  country: '',
  province_id: null, district_id: null,
  commune_id: null,  village_id: null,
  address: '',
  address_line2: ''
}
```

- [ ] **Step 2: Update save payload**

Add `country` and `address_line2` to the spread block (see amendment draft Section 3.3).

- [ ] **Step 3: Update edit-mode loader**

In `customers.js`, when hydrating the form from an existing customer record, populate `form.addresses.country` and `form.addresses.address_line2` from the loaded record.

- [ ] **Step 4: Extend Model fillable**

In `app/Models/Customer.php`, add `'country'` and `'address_line2'` to the `#[Fillable(...)]` attribute.

- [ ] **Step 5: Expose in CustomerResource**

Add `country` and `address_line2` to the resource's output.

- [ ] **Step 6: Extend FormRequests with conditional rule**

In both `StoreCustomerRequest` and `UpdateCustomerRequest`, add:

```php
public function rules(): array {
    return [
        // ... existing rules ...
        'country'       => 'nullable|string|max:100',
        'address_line2' => 'nullable|string|max:500',
    ];
}

public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void {
    $validator->after(function ($v) {
        $country = $this->input('country');
        if (!empty($country)) {
            if (empty($this->input('address'))) {
                $v->errors()->add('address', 'Address is required when country is set (non-Cambodia).');
            }
            foreach (['province_id', 'district_id', 'commune_id', 'village_id'] as $fk) {
                if (!empty($this->input($fk))) {
                    $v->errors()->add($fk, "Cannot set {$fk} when country is set (non-Cambodia rows must not use the Cambodia cascade).");
                }
            }
        }
    });
}
```

- [ ] **Step 7: Verify**

```bash
php artisan test --filter=CustomerTest
npm run build
```

- [ ] **Step 8: Commit**

```bash
git add resources/js/pages/master/CustomerForm.vue resources/js/stores/customers.js \
        app/Models/Customer.php app/Http/Resources/CustomerResource.php \
        app/Http/Requests/StoreCustomerRequest.php app/Http/Requests/UpdateCustomerRequest.php
git commit -m "feat(customer): outside-Cambodia address fields and validation"
```

---

### Task 31: SupplierForm — extend form model + save payload + resource/model

Same as Task 30, but for the Supplier entity. Files:

- Modify: `resources/js/pages/master/SupplierForm.vue`
- Modify: `resources/js/stores/suppliers.js`
- Modify: `app/Models/Supplier.php`
- Modify: `app/Http/Resources/SupplierResource.php`
- Modify: `app/Http/Requests/StoreSupplierRequest.php`, `UpdateSupplierRequest.php`

- [ ] **Steps 1–8**: Replicate Task 30 for the Supplier entity.

- [ ] **Step 9: Commit**

```bash
git commit -m "feat(supplier): outside-Cambodia address fields and validation"
```

---

### Task 32: WarehouseForm — extend form model + save payload + resource/model

Same as Task 30, but for the Warehouse entity. Files:

- Modify: `resources/js/pages/master/WarehouseForm.vue`
- Modify: `resources/js/stores/warehouses.js`
- Modify: `app/Models/Warehouse.php`
- Modify: `app/Http/Resources/WarehouseResource.php`
- Modify: `app/Http/Requests/StoreWarehouseRequest.php`, `UpdateWarehouseRequest.php`

- [ ] **Steps 1–8**: Replicate Task 30 for the Warehouse entity.

- [ ] **Step 9: Commit**

```bash
git commit -m "feat(warehouse): outside-Cambodia address fields and validation"
```

---

### Task 33: Update list views for non-Cambodia row rendering

**Files:**
- Modify: `resources/js/pages/master/Customers.vue`
- Modify: `resources/js/pages/master/Suppliers.vue`
- Modify: `resources/js/pages/master/Warehouses.vue`

- [ ] **Step 1: Update each list view's address rendering**

The existing format is `"#123, St. 271 • Phnom Penh • Doun Penh • Sangkat Wat Phnom"` (Cambodia hierarchical). Extend the renderer so it branches on `customer.country` (or equivalent):

```vue
<template v-if="customer.country">
  {{ customer.address }}<template v-if="customer.address_line2"> • {{ customer.address_line2 }}</template> • {{ customer.country }}
</template>
<template v-else>
  <!-- existing Cambodia hierarchical format -->
</template>
```

- [ ] **Step 2: Verify with build**

```bash
npm run build
```

Expected: build succeeds.

- [ ] **Step 3: Manual smoke**

Create a Supplier with `country = "Vietnam"`, `address = "123 Le Loi, District 1"`, `address_line2 = "Floor 3"`. Verify the Suppliers list renders `"123 Le Loi, District 1 • Floor 3 • Vietnam"`. Verify a Cambodia row still renders hierarchically.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/master/Customers.vue \
        resources/js/pages/master/Suppliers.vue \
        resources/js/pages/master/Warehouses.vue
git commit -m "feat(master-lists): render non-Cambodia address rows"
```

---

### Task 34: OutsideCambodiaAddressTest — feature test

**Files:**
- Create: `tests/Feature/OutsideCambodiaAddressTest.php`

- [ ] **Step 1: Create the test file**

```bash
php artisan make:test OutsideCambodiaAddressTest
```

Replace the generated file with:

```php
<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutsideCambodiaAddressTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_customer_can_be_created_with_country_and_address_only(): void
    {
        $response = $this->postJson('/api/customers', [
            'name'    => 'HCM Import Co.',
            'country' => 'Vietnam',
            'address' => '123 Le Loi, District 1',
            'address_line2' => 'Floor 3',
            'type'    => 'wholesale',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('customers', [
            'name'    => 'HCM Import Co.',
            'country' => 'Vietnam',
            'address' => '123 Le Loi, District 1',
            'address_line2' => 'Floor 3',
            'province_id' => null,
            'district_id' => null,
            'commune_id'  => null,
            'village_id'  => null,
        ]);
    }

    public function test_mixed_country_and_province_is_rejected(): void
    {
        $province = \App\Models\Addresses\Province::factory()->create();

        $response = $this->postJson('/api/customers', [
            'name'       => 'Bad Row',
            'country'    => 'Vietnam',
            'address'    => '123 Le Loi',
            'province_id' => $province->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['province_id']);
    }

    public function test_non_cambodia_row_without_address_is_rejected(): void
    {
        $response = $this->postJson('/api/customers', [
            'name'    => 'No Address',
            'country' => 'Thailand',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['address']);
    }

    public function test_supplier_round_trip_with_country(): void
    {
        $supplier = Supplier::create([
            'name'    => 'Bangkok Trade',
            'country' => 'Thailand',
            'address' => '456 Sukhumvit',
        ]);

        $this->assertDatabaseHas('suppliers', ['name' => 'Bangkok Trade', 'country' => 'Thailand']);

        $supplier->update(['address_line2' => 'Unit 5']);
        $this->assertDatabaseHas('suppliers', ['name' => 'Bangkok Trade', 'address_line2' => 'Unit 5']);
    }

    public function test_warehouse_round_trip_with_country(): void
    {
        $warehouse = Warehouse::create([
            'name'    => 'SGN Hub',
            'country' => 'Vietnam',
            'address' => 'Tan Binh Industrial Park',
        ]);

        $this->assertDatabaseHas('warehouses', ['name' => 'SGN Hub', 'country' => 'Vietnam']);
    }

    public function test_cambodia_row_still_works(): void
    {
        // Regression guard: a Cambodia row (country = null) is unchanged
        $province = \App\Models\Addresses\Province::factory()->create();

        $response = $this->postJson('/api/customers', [
            'name'        => 'Phnom Penh Shop',
            'province_id' => $province->id,
            'address'     => '#45, St. 271',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('customers', [
            'name'        => 'Phnom Penh Shop',
            'country'     => null,
            'province_id' => $province->id,
            'address'     => '#45, St. 271',
        ]);
    }
}
```

> **Note:** if `Province::factory()` is not defined, replace with a manual `Province::create([...])` call seeded with a known `code`/`name_en`/`name_km`.

- [ ] **Step 2: Run the test**

```bash
php artisan test --filter=OutsideCambodiaAddressTest
```

Expected: 6 tests pass.

- [ ] **Step 3: Run the full test suite to confirm no regressions**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/OutsideCambodiaAddressTest.php
git commit -m "test(addresses): outside-Cambodia round-trips and validation"
```

---

## Acceptance Criteria Checklist

From the spec — each maps to a task in this plan:

| # | Criterion | Task(s) |
|---|---|---|
| 1 | Migrations succeed; 4 new tables + FK columns | 1–4, 13–15 |
| 2 | Seeders populate ≥25/200/1500/13000 rows | 5–9 |
| 3 | Provinces endpoint returns localized labels | 10 |
| 4 | Cascading endpoints filter by parent; `?q=` searches EN + KM | 10–12 |
| 5 | Customer/Supplier/Warehouse forms show 4 cascading selects | 21–23 |
| 6 | Lower selects disabled until parent chosen | 20 |
| 7 | Saving persists all 4 FKs + free-text address | 16–18, 21–23 |
| 8 | Editing restores cascader selections | 21–23 |
| 9 | List views render hierarchical address or fall back to `address` text | 24–26 |
| 10 | Khmer locale shows Khmer labels | 10, 20 |
| 11 | All tests pass; no regressions | 27 |
| 12 *(amendment)* | Forms show outside-Cambodia toggle that hides the 4 selects | 29, 30–32 |
| 13 *(amendment)* | Saving a non-Cambodia row persists `country` non-empty, FK columns NULL, `address` non-empty, `address_line2` exact | 28, 30–32, 34 |
| 14 *(amendment)* | Validation rejects mixed rows (country + FK) with 422 | 30–32, 34 |
| 15 *(amendment)* | List views render non-Cambodia rows correctly | 33 |
| 16 *(amendment)* | Cambodia rows still load and render after the migration | 28, 34 |
| 17 *(amendment)* | All new tests pass; no regressions | 34 |

---

## Notes for the Implementer

- If `kh-addresses.json` cannot be downloaded automatically in Task 5, vendor a small subset (e.g. Phnom Penh only) for development. The seeder is shape-driven, not size-driven, so partial data works.
- Existing rows in `customers`, `suppliers`, `warehouses` are NOT touched by the migration — they keep their original `address` text and get NULL FK columns. No backfill.
- The component's `BaseSelect` already exists at `resources/js/components/ui/BaseSelect.vue` and accepts `options` shaped as `{ value, label }`. If you need to override option shape, check that file first.
- If you need to clear the cache during testing: `php artisan cache:clear`.
- The `address` field remains on the table — do NOT remove it. Legacy data and free-text use cases depend on it.
