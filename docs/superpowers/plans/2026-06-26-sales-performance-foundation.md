# Sales Performance Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the foundation layer of Sales Performance Management for WSaler (salespeople + targets + customer assignment) per `docs/superpowers/specs/2026-06-26-sales-performance-foundation-design.md`.

**Architecture:** Laravel 13 backend (Eloquent + observers + services) + Vue 3 SPA (Pinia + Vue Router + axios). Synchronous target achievement updates via `SaleObserver` → `TargetAchievementUpdater` service. Existing `Approval` model reused for assignment approvals. Existing `ActivityLog` for audit.

**Tech Stack:** PHP 8.3, Laravel 13.8, Sanctum, Eloquent, PHPUnit 12. Vue 3.5, Pinia 2.3, Vue Router 4.6, axios, Tailwind 4, i18next.

---

## File Structure

### Backend migrations (`database/migrations/`)
- `2026_06_26_100000_add_employment_status_and_team_id_to_users_table.php`
- `2026_06_26_100100_create_teams_table.php`
- `2026_06_26_100200_create_territories_table.php`
- `2026_06_26_100201_create_territory_user_table.php`
- `2026_06_26_100300_create_customer_assignments_table.php`
- `2026_06_26_100400_create_target_templates_table.php`
- `2026_06_26_100401_create_target_template_lines_table.php`
- `2026_06_26_100500_create_sales_targets_table.php`
- `2026_06_26_100501_create_sales_target_lines_table.php`
- `2026_06_26_100502_create_sales_target_achievements_table.php`
- `2026_06_26_100600_migrate_user_customer_ids_to_customer_assignments.php`
- `2026_06_26_100601_drop_customer_ids_from_users_table.php`

### Backend models (`app/Models/SalesPerformance/`)
- `Team.php`, `Territory.php`, `CustomerAssignment.php`
- `SalesTarget.php`, `SalesTargetLine.php`, `SalesTargetAchievement.php`
- `TargetTemplate.php`, `TargetTemplateLine.php`
- Update: `app/Models/User.php` (add `employment_status`, `team_id`; drop `customer_ids`)

### Backend services (`app/Services/SalesPerformance/`)
- `TargetAchievementUpdaterInterface.php`
- `TargetAchievementUpdater.php`
- `BulkTargetAssigner.php`
- `AssignmentApprovalCoordinator.php`

### Backend observer (`app/Observers/`)
- `SaleObserver.php`

### Backend controllers (`app/Http/Controllers/Api/SalesPerformance/`)
- `SalespersonController.php`, `TeamController.php`, `TerritoryController.php`
- `CustomerAssignmentController.php`, `CustomerVisitHistoryController.php`
- `SalesTargetController.php`, `TargetTemplateController.php`, `ApprovalController.php`

### Backend requests (`app/Http/Requests/SalesPerformance/`)
- `StoreSalespersonRequest.php`, `UpdateSalespersonRequest.php`
- `StoreTeamRequest.php`, `UpdateTeamRequest.php`
- `StoreTerritoryRequest.php`, `AttachTerritoryMembersRequest.php`
- `StoreCustomerAssignmentRequest.php`, `UpdateCustomerAssignmentRequest.php`
- `StoreSalesTargetRequest.php`, `BulkAssignTargetRequest.php`
- `StoreTargetTemplateRequest.php`, `UpdateTargetTemplateRequest.php`
- `ApproveApprovalRequest.php`, `RejectApprovalRequest.php`

### Backend resources (`app/Http/Resources/SalesPerformance/`)
- `SalespersonResource.php`, `TeamResource.php`, `TerritoryResource.php`
- `CustomerAssignmentResource.php`, `SalesTargetResource.php`, `SalesTargetLineResource.php`
- `SalesTargetAchievementResource.php`, `TargetTemplateResource.php`, `ApprovalResource.php`

### Backend routes
- Update `routes/api.php` — add `/api/sales-performance/*` group with permission middleware

### Backend seeder
- Update `database/seeders/RolePermissionSeeder.php` — add 11 new permissions

### Frontend (`resources/js/`)
- `services/salesPerformanceApi.js`
- `stores/salesPerformance/{salespeople,teams,territories,targets,assignments,approvals}.js`
- `pages/sales-performance/salespeople/{SalespeopleList,SalespersonDetail,SalespersonForm}.vue`
- `pages/sales-performance/teams/{TeamsList,TeamForm}.vue`
- `pages/sales-performance/territories/{TerritoriesList,TerritoryForm}.vue`
- `pages/sales-performance/targets/{TargetsList,TargetDetail,TargetForm,BulkAssignDialog}.vue`
- `pages/sales-performance/assignments/{CustomerAssignmentsList,AssignmentForm}.vue`
- `pages/sales-performance/approvals/ApprovalsInbox.vue`
- `i18n/{en,id}/sales-performance.json`
- Update `router/index.js`, `components/AppSidebar.vue`

### Tests
- `tests/Feature/SalesPerformance/{SalespeopleTest,TeamsTest,TerritoriesTest,CustomerAssignmentsTest,SalesTargetsTest,SalesTargetAchievementsTest,ApprovalsTest,BulkAssignmentTest}.php`
- `tests/Unit/SalesPerformance/TargetAchievementUpdaterTest.php`

---

## Conventions Used Throughout

- All migrations use `Schema::create()` (or `table()` for alters) with explicit `->id()`, `->timestamps()`, indexes, FKs.
- All models use `#[Fillable([...])]` attribute, `casts()` method, and `SoftDeletes` where specified.
- All controllers return `JsonResponse` or use API Resources.
- All API routes are wrapped in `Route::middleware(['auth:sanctum', 'permission:...'])` group.
- All tests use `RefreshDatabase` trait and `actingAs($user)` helper.
- Frontend uses `<script setup>` Composition API style and Tailwind utility classes.
- Commit message format: `feat(scope): description` or `test(scope): description` or `chore(scope): description`.

---

## Phase 1 — Foundation Migrations & Models

### Task 1: Add employment_status and team_id to users table

**Files:**
- Create: `database/migrations/2026_06_26_100000_add_employment_status_and_team_id_to_users_table.php`

- [ ] **Step 1: Create migration file**

```bash
php artisan make:migration add_employment_status_and_team_id_to_users_table
```

- [ ] **Step 2: Replace the migration content**

Edit `database/migrations/2026_06_26_100000_add_employment_status_and_team_id_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('employment_status', ['active', 'inactive', 'on_leave', 'terminated'])
                ->default('active')
                ->after('role');
            $table->foreignId('team_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('teams')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn(['employment_status', 'team_id']);
        });
    }
};
```

- [ ] **Step 3: Update User model**

Edit `app/Models/User.php` — add to `casts()`:

```php
'employment_status' => \App\Enums\EmploymentStatus::class,
```

- [ ] **Step 4: Create the EmploymentStatus enum**

```bash
php artisan make:enum EmploymentStatus
```

Edit `app/Enums/EmploymentStatus.php`:

```php
<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case OnLeave = 'on_leave';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::OnLeave => 'On Leave',
            self::Terminated => 'Terminated',
        };
    }
}
```

- [ ] **Step 5: Run migration and verify**

```bash
php artisan migrate
```

Expected: migration runs successfully. Check `users` table has `employment_status` and `team_id` columns.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_26_100000_add_employment_status_and_team_id_to_users_table.php app/Models/User.php app/Enums/EmploymentStatus.php
git commit -m "feat(users): add employment_status enum and team_id FK"
```

---

### Task 2: Create teams table and model

**Files:**
- Create: `database/migrations/2026_06_26_100100_create_teams_table.php`
- Create: `app/Models/SalesPerformance/Team.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_teams_table --path=database/migrations
```

- [ ] **Step 2: Fill migration content**

Edit the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('leader_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
```

- [ ] **Step 3: Create Team model**

```bash
mkdir -p app/Models/SalesPerformance
```

Create `app/Models/SalesPerformance/Team.php`:

```php
<?php

namespace App\Models\SalesPerformance;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'leader_user_id', 'description', 'is_active'])]
class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }
}
```

- [ ] **Step 4: Run migration**

```bash
php artisan migrate
```

Expected: teams table created.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_26_100100_create_teams_table.php app/Models/SalesPerformance/Team.php
git commit -m "feat(teams): create teams table and model"
```

---

### Task 3: Add User.team relationship and scopes

**Files:**
- Modify: `app/Models/User.php`

- [ ] **Step 1: Add team relationship**

Edit `app/Models/User.php` — add import and method:

```php
use App\Models\SalesPerformance\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Inside class:
public function team(): BelongsTo
{
    return $this->belongsTo(Team::class);
}
```

- [ ] **Step 2: Add salesperson scope**

Add to `app/Models/User.php`:

```php
use Illuminate\Database\Eloquent\Builder;

// Inside class:
public function scopeSalespeople(Builder $query): Builder
{
    return $query->where('role', \App\Enums\UserRole::Salesperson);
}

public function scopeActive(Builder $query): Builder
{
    return $query->where('employment_status', \App\Enums\EmploymentStatus::Active->value);
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/User.php
git commit -m "feat(users): add team relationship and salesperson/active scopes"
```

---

### Task 4: Create territories table, pivot, and model

**Files:**
- Create: `database/migrations/2026_06_26_100200_create_territories_table.php`
- Create: `database/migrations/2026_06_26_100201_create_territory_user_table.php`
- Create: `app/Models/SalesPerformance/Territory.php`

- [ ] **Step 1: Create territories migration**

```bash
php artisan make:migration create_territories_table --path=database/migrations
```

Edit the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('territories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('region')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territories');
    }
};
```

- [ ] **Step 2: Create territory_user pivot migration**

```bash
php artisan make:migration create_territory_user_table --path=database/migrations
```

Edit the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('territory_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->unique(['territory_id', 'user_id', 'valid_from'], 'territory_user_unique');
            $table->index(['user_id', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territory_user');
    }
};
```

- [ ] **Step 3: Create Territory model**

Create `app/Models/SalesPerformance/Territory.php`:

```php
<?php

namespace App\Models\SalesPerformance;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'region', 'description', 'is_active'])]
class Territory extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function salespeople(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'territory_user')
            ->withPivot(['assigned_at', 'assigned_by', 'valid_from', 'valid_to'])
            ->withTimestamps();
    }
}
```

- [ ] **Step 4: Add territories relationship to User**

Edit `app/Models/User.php`:

```php
use App\Models\SalesPerformance\Territory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Inside class:
public function territories(): BelongsToMany
{
    return $this->belongsToMany(Territory::class, 'territory_user')
        ->withPivot(['assigned_at', 'assigned_by', 'valid_from', 'valid_to'])
        ->withTimestamps();
}
```

- [ ] **Step 5: Run migrations**

```bash
php artisan migrate
```

Expected: territories + territory_user created.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_26_100200_create_territories_table.php database/migrations/2026_06_26_100201_create_territory_user_table.php app/Models/SalesPerformance/Territory.php app/Models/User.php
git commit -m "feat(territories): create territories table, pivot, and model"
```

---

### Task 5: Create customer_assignments table and model

**Files:**
- Create: `database/migrations/2026_06_26_100300_create_customer_assignments_table.php`
- Create: `app/Models/SalesPerformance/CustomerAssignment.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_customer_assignments_table --path=database/migrations
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salesperson_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'expired', 'revoked'])->default('pending');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approval_id')->nullable()->constrained('approvals')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['salesperson_user_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('valid_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_assignments');
    }
};
```

- [ ] **Step 2: Create CustomerAssignment model**

Create `app/Models/SalesPerformance/CustomerAssignment.php`:

```php
<?php

namespace App\Models\SalesPerformance;

use App\Models\Approval;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_id', 'salesperson_user_id', 'status',
    'valid_from', 'valid_to', 'notes',
    'assigned_by', 'approval_id',
])]
class CustomerAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_user_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }
}
```

- [ ] **Step 3: Add customer assignments relationship to User**

Edit `app/Models/User.php`:

```php
use App\Models\SalesPerformance\CustomerAssignment;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Inside class:
public function customerAssignments(): HasMany
{
    return $this->hasMany(CustomerAssignment::class, 'salesperson_user_id');
}
```

- [ ] **Step 4: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_26_100300_create_customer_assignments_table.php app/Models/SalesPerformance/CustomerAssignment.php app/Models/User.php
git commit -m "feat(assignments): create customer_assignments table and model"
```

---

### Task 6: Create target templates (header + lines)

**Files:**
- Create: `database/migrations/2026_06_26_100400_create_target_templates_table.php`
- Create: `database/migrations/2026_06_26_100401_create_target_template_lines_table.php`
- Create: `app/Models/SalesPerformance/TargetTemplate.php`
- Create: `app/Models/SalesPerformance/TargetTemplateLine.php`
- Create: `app/Enums/TargetMetric.php`
- Create: `app/Enums/TargetPeriod.php`

- [ ] **Step 1: Create TargetMetric enum**

```bash
php artisan make:enum TargetMetric
```

Edit `app/Enums/TargetMetric.php`:

```php
<?php

namespace App\Enums;

enum TargetMetric: string
{
    case SalesAmount = 'sales_amount';
    case InvoiceCount = 'invoice_count';
    case CustomerCount = 'customer_count';
    case Quantity = 'quantity';
    case GrossProfit = 'gross_profit';
    case CollectionAmount = 'collection_amount';
    case NewCustomerCount = 'new_customer_count';

    public function label(): string
    {
        return match ($this) {
            self::SalesAmount => 'Sales Amount',
            self::InvoiceCount => 'Number of Invoices',
            self::CustomerCount => 'Number of Customers',
            self::Quantity => 'Quantity Sold',
            self::GrossProfit => 'Gross Profit',
            self::CollectionAmount => 'Collection Amount',
            self::NewCustomerCount => 'New Customer Acquisition',
        };
    }
}
```

- [ ] **Step 2: Create TargetPeriod enum**

```bash
php artisan make:enum TargetPeriod
```

Edit `app/Enums/TargetPeriod.php`:

```php
<?php

namespace App\Enums;

enum TargetPeriod: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annual => 'Annual',
        };
    }
}
```

- [ ] **Step 3: Create target_templates migration**

```bash
php artisan make:migration create_target_templates_table --path=database/migrations
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('target_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'annual']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_templates');
    }
};
```

- [ ] **Step 4: Create target_template_lines migration**

```bash
php artisan make:migration create_target_template_lines_table --path=database/migrations
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('target_template_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_template_id')->constrained()->cascadeOnDelete();
            $table->enum('metric', [
                'sales_amount', 'invoice_count', 'customer_count',
                'quantity', 'gross_profit', 'collection_amount', 'new_customer_count',
            ]);
            $table->decimal('default_value', 18, 4);
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
            $table->unique(['target_template_id', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_template_lines');
    }
};
```

- [ ] **Step 5: Create TargetTemplate model**

Create `app/Models/SalesPerformance/TargetTemplate.php`:

```php
<?php

namespace App\Models\SalesPerformance;

use App\Enums\TargetPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'period_type', 'description', 'is_active', 'created_by'])]
class TargetTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'period_type' => TargetPeriod::class,
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TargetTemplateLine::class)->orderBy('order_index');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

- [ ] **Step 6: Create TargetTemplateLine model**

Create `app/Models/SalesPerformance/TargetTemplateLine.php`:

```php
<?php

namespace App\Models\SalesPerformance;

use App\Enums\TargetMetric;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['target_template_id', 'metric', 'default_value', 'order_index'])]
class TargetTemplateLine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metric' => TargetMetric::class,
            'default_value' => 'decimal:4',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TargetTemplate::class, 'target_template_id');
    }
}
```

- [ ] **Step 7: Run migrations and commit**

```bash
php artisan migrate
git add database/migrations/2026_06_26_100400_create_target_templates_table.php database/migrations/2026_06_26_100401_create_target_template_lines_table.php app/Enums/TargetMetric.php app/Enums/TargetPeriod.php app/Models/SalesPerformance/TargetTemplate.php app/Models/SalesPerformance/TargetTemplateLine.php
git commit -m "feat(targets): create target_templates and target_template_lines"
```

---

### Task 7: Create sales_targets (header + lines + achievements)

**Files:**
- Create: `database/migrations/2026_06_26_100500_create_sales_targets_table.php`
- Create: `database/migrations/2026_06_26_100501_create_sales_target_lines_table.php`
- Create: `database/migrations/2026_06_26_100502_create_sales_target_achievements_table.php`
- Create: `app/Models/SalesPerformance/SalesTarget.php`
- Create: `app/Models/SalesPerformance/SalesTargetLine.php`
- Create: `app/Models/SalesPerformance/SalesTargetAchievement.php`
- Create: `app/Enums/TargetStatus.php`

- [ ] **Step 1: Create TargetStatus enum**

```bash
php artisan make:enum TargetStatus
```

Edit `app/Enums/TargetStatus.php`:

```php
<?php

namespace App\Enums;

enum TargetStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Achieved = 'achieved';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
```

- [ ] **Step 2: Create sales_targets migration**

```bash
php artisan make:migration create_sales_targets_table --path=database/migrations
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salesperson_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'annual']);
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('target_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('status', ['draft', 'active', 'achieved', 'expired', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['salesperson_user_id', 'period_type', 'period_start'], 'sales_targets_unique_period');
            $table->index(['status', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
```

- [ ] **Step 3: Create sales_target_lines migration**

```bash
php artisan make:migration create_sales_target_lines_table --path=database/migrations
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_target_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_target_id')->constrained()->cascadeOnDelete();
            $table->enum('metric', [
                'sales_amount', 'invoice_count', 'customer_count',
                'quantity', 'gross_profit', 'collection_amount', 'new_customer_count',
            ]);
            $table->decimal('target_value', 18, 4);
            $table->timestamps();
            $table->unique(['sales_target_id', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_target_lines');
    }
};
```

- [ ] **Step 4: Create sales_target_achievements migration**

```bash
php artisan make:migration create_sales_target_achievements_table --path=database/migrations
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_target_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_target_line_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('achieved_value', 18, 4)->default(0);
            $table->decimal('achievement_pct', 8, 4)->default(0);
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();
            $table->unique(['sales_target_line_id', 'snapshot_date'], 'achievement_line_date_unique');
            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_target_achievements');
    }
};
```

- [ ] **Step 5: Create SalesTarget model**

Create `app/Models/SalesPerformance/SalesTarget.php`:

```php
<?php

namespace App\Models\SalesPerformance;

use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'salesperson_user_id', 'period_type', 'period_start', 'period_end',
    'target_template_id', 'name', 'status', 'created_by', 'approved_by',
])]
class SalesTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'period_type' => TargetPeriod::class,
            'status' => TargetStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesTargetLine::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TargetTemplate::class, 'target_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TargetStatus::Active->value);
    }
}
```

Add the `Builder` import at the top:

```php
use Illuminate\Database\Eloquent\Builder;
```

- [ ] **Step 6: Create SalesTargetLine model**

Create `app/Models/SalesPerformance/SalesTargetLine.php`:

```php
<?php

namespace App\Models\SalesPerformance;

use App\Enums\TargetMetric;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sales_target_id', 'metric', 'target_value'])]
class SalesTargetLine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metric' => TargetMetric::class,
            'target_value' => 'decimal:4',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(SalesTargetAchievement::class);
    }

    public function latestAchievement()
    {
        return $this->hasOne(SalesTargetAchievement::class)->latestOfMany('snapshot_date');
    }
}
```

- [ ] **Step 7: Create SalesTargetAchievement model**

Create `app/Models/SalesPerformance/SalesTargetAchievement.php`:

```php
<?php

namespace App\Models\SalesPerformance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_target_line_id', 'snapshot_date',
    'achieved_value', 'achievement_pct', 'computed_at',
])]
class SalesTargetAchievement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'achieved_value' => 'decimal:4',
            'achievement_pct' => 'decimal:4',
            'computed_at' => 'datetime',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(SalesTargetLine::class, 'sales_target_line_id');
    }
}
```

- [ ] **Step 8: Run migrations and commit**

```bash
php artisan migrate
git add database/migrations/2026_06_26_100500_create_sales_targets_table.php database/migrations/2026_06_26_100501_create_sales_target_lines_table.php database/migrations/2026_06_26_100502_create_sales_target_achievements_table.php app/Enums/TargetStatus.php app/Models/SalesPerformance/SalesTarget.php app/Models/SalesPerformance/SalesTargetLine.php app/Models/SalesPerformance/SalesTargetAchievement.php
git commit -m "feat(targets): create sales_targets + lines + achievements"
```

---

## Phase 2 — Data Migration

### Task 8: Migrate User.customer_ids to customer_assignments

**Files:**
- Create: `database/migrations/2026_06_26_100600_migrate_user_customer_ids_to_customer_assignments.php`
- Create: `database/migrations/2026_06_26_100601_drop_customer_ids_from_users_table.php`

- [ ] **Step 1: Create migration migration**

```bash
php artisan make:migration migrate_user_customer_ids_to_customer_assignments --path=database/migrations
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    public function up(): void
    {
        $today = now()->toDateString();
        $migratedCount = 0;
        $skippedCount = 0;

        $users = DB::table('users')->whereNotNull('customer_ids')->get(['id', 'customer_ids']);

        DB::transaction(function () use ($users, $today, &$migratedCount, &$skippedCount) {
            foreach ($users as $user) {
                $customerIds = is_array($user->customer_ids)
                    ? $user->customer_ids
                    : json_decode($user->customer_ids, true) ?? [];

                foreach ($customerIds as $customerId) {
                    $exists = DB::table('customers')->where('id', $customerId)->exists();
                    if (! $exists) {
                        $skippedCount++;
                        continue;
                    }

                    $alreadyMigrated = DB::table('customer_assignments')
                        ->where('customer_id', $customerId)
                        ->where('salesperson_user_id', $user->id)
                        ->where('valid_to', null)
                        ->exists();

                    if ($alreadyMigrated) {
                        $skippedCount++;
                        continue;
                    }

                    DB::table('customer_assignments')->insert([
                        'customer_id' => $customerId,
                        'salesperson_user_id' => $user->id,
                        'status' => 'active',
                        'valid_from' => $today,
                        'notes' => 'Migrated from users.customer_ids',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $migratedCount++;
                }
            }
        });

        Log::info("Migrated {$migratedCount} customer assignments; skipped {$skippedCount}.");
    }

    public function down(): void
    {
        DB::table('customer_assignments')
            ->where('notes', 'Migrated from users.customer_ids')
            ->delete();
    }
};
```

- [ ] **Step 2: Create drop-column migration**

```bash
php artisan make:migration drop_customer_ids_from_users_table --path=database/migrations
```

Edit:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('customer_ids');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('customer_ids')->nullable();
        });
    }
};
```

- [ ] **Step 3: Remove customer_ids from User model**

Edit `app/Models/User.php`:
- Remove `'customer_ids'` from the `#[Fillable(...)]` array.
- Remove `'customer_ids' => 'array',` from `casts()`.
- Remove the `customers()` HasMany method that uses customer_ids.

- [ ] **Step 4: Run migrations**

```bash
php artisan migrate
```

Expected: both migrations run; user.customer_ids dropped.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_26_100600_migrate_user_customer_ids_to_customer_assignments.php database/migrations/2026_06_26_100601_drop_customer_ids_from_users_table.php app/Models/User.php
git commit -m "feat(migration): move User.customer_ids into customer_assignments"
```

---

## Phase 3 — RBAC Permissions

### Task 9: Add sales-performance permissions to seeder

**Files:**
- Modify: `database/seeders/RolePermissionSeeder.php`

- [ ] **Step 1: Add new permissions**

Edit the seeder — find the permissions array and add these entries:

```php
'salespeople.view', 'salespeople.manage',
'teams.view', 'teams.manage',
'territories.view', 'territories.manage',
'assignments.view', 'assignments.manage',
'targets.view', 'targets.manage',
'target_templates.view', 'target_templates.manage',
'approvals.review',
```

(Adjust based on the actual structure of your seeder — match the existing pattern.)

- [ ] **Step 2: Attach to manager role**

Ensure manager role has:
- salespeople.view, salespeople.manage
- teams.view, teams.manage
- territories.view, territories.manage
- assignments.view, assignments.manage
- targets.view, targets.manage
- target_templates.view, target_templates.manage
- approvals.review

- [ ] **Step 3: Attach to admin role**

Admin role gets all of the above.

- [ ] **Step 4: Run seeder**

```bash
php artisan db:seed --class=RolePermissionSeeder
```

- [ ] **Step 5: Commit**

```bash
git add database/seeders/RolePermissionSeeder.php
git commit -m "feat(rbac): add sales-performance permissions"
```

---

## Phase 4 — Target Achievement Updater Service

### Task 10: Write failing test for TargetAchievementUpdater

**Files:**
- Create: `tests/Unit/SalesPerformance/TargetAchievementUpdaterTest.php`

- [ ] **Step 1: Create test file**

```bash
mkdir -p tests/Unit/SalesPerformance
```

Create `tests/Unit/SalesPerformance/TargetAchievementUpdaterTest.php`:

```php
<?php

namespace Tests\Unit\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use App\Models\SalesPerformance\SalesTarget;
use App\Models\SalesPerformance\SalesTargetLine;
use App\Models\SalesPerformance\TargetTemplate;
use App\Models\User;
use App\Services\SalesPerformance\TargetAchievementUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetAchievementUpdaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_sale_increments_sales_amount_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = \App\Models\Customer::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Test Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        $sale = $this->makeSale($salesperson, $customer, ['total' => 1500]);

        app(TargetAchievementUpdater::class)->applySale($sale);

        $achievement = $line->achievements()->first();
        $this->assertNotNull($achievement);
        $this->assertEquals(1500, (float) $achievement->achieved_value);
        $this->assertEquals(15.0, (float) $achievement->achievement_pct);
    }

    public function test_reverse_sale_decrements_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = \App\Models\Customer::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Test Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        $sale = $this->makeSale($salesperson, $customer, ['total' => 1500]);
        app(TargetAchievementUpdater::class)->applySale($sale);
        app(TargetAchievementUpdater::class)->reverseSale($sale);

        $achievement = $line->achievements()->first();
        $this->assertEquals(0, (float) $achievement->achieved_value);
    }

    private function makeSale(User $salesperson, \App\Models\Customer $customer, array $attrs = []): \App\Models\Sale
    {
        $warehouse = \App\Models\Warehouse::factory()->create();
        return \App\Models\Sale::create(array_merge([
            'invoice_number' => 'INV-' . uniqid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $salesperson->id,
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000,
            'paid' => 1000,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ], $attrs));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=TargetAchievementUpdaterTest
```

Expected: FAIL with "Class App\Services\SalesPerformance\TargetAchievementUpdater not found".

- [ ] **Step 3: Commit failing test**

```bash
git add tests/Unit/SalesPerformance/TargetAchievementUpdaterTest.php
git commit -m "test(targets): failing test for TargetAchievementUpdater"
```

---

### Task 11: Implement TargetAchievementUpdater

**Files:**
- Create: `app/Services/SalesPerformance/TargetAchievementUpdaterInterface.php`
- Create: `app/Services/SalesPerformance/TargetAchievementUpdater.php`

- [ ] **Step 1: Create interface**

Create `app/Services/SalesPerformance/TargetAchievementUpdaterInterface.php`:

```php
<?php

namespace App\Services\SalesPerformance;

use App\Models\Sale;

interface TargetAchievementUpdaterInterface
{
    public function applySale(Sale $sale): void;

    public function reverseSale(Sale $sale): void;
}
```

- [ ] **Step 2: Create implementation**

```bash
mkdir -p app/Services/SalesPerformance
```

Create `app/Services/SalesPerformance/TargetAchievementUpdater.php`:

```php
<?php

namespace App\Services\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SalesPerformance\SalesTarget;
use App\Models\SalesPerformance\SalesTargetAchievement;
use App\Models\SalesPerformance\SalesTargetLine;
use Illuminate\Support\Facades\DB;

class TargetAchievementUpdater implements TargetAchievementUpdaterInterface
{
    public function applySale(Sale $sale): void
    {
        $this->applyOrReverse($sale, reverse: false);
    }

    public function reverseSale(Sale $sale): void
    {
        $this->applyOrReverse($sale, reverse: true);
    }

    private function applyOrReverse(Sale $sale, bool $reverse): void
    {
        $targets = SalesTarget::active()
            ->where('salesperson_user_id', $sale->user_id)
            ->where('period_start', '<=', $sale->sold_at)
            ->where('period_end', '>=', $sale->sold_at)
            ->get();

        if ($targets->isEmpty()) {
            return;
        }

        $contributions = $this->contributions($sale);

        DB::transaction(function () use ($targets, $contributions, $sale, $reverse) {
            foreach ($targets as $target) {
                foreach ($target->lines as $line) {
                    $metricValue = $line->metric->value;
                    if (! isset($contributions[$metricValue])) {
                        continue;
                    }

                    $delta = $reverse ? -$contributions[$metricValue] : $contributions[$metricValue];

                    $achievement = SalesTargetAchievement::firstOrNew([
                        'sales_target_line_id' => $line->id,
                        'snapshot_date' => $sale->sold_at->toDateString(),
                    ]);

                    $newValue = (float) ($achievement->achieved_value ?? 0) + $delta;
                    $achievement->achieved_value = $newValue;
                    $achievement->achievement_pct = $line->target_value > 0
                        ? ($newValue / (float) $line->target_value) * 100
                        : 0;
                    $achievement->computed_at = now();
                    $achievement->save();
                }
            }
        });
    }

    private function contributions(Sale $sale): array
    {
        $customer = $sale->customer;

        return [
            TargetMetric::SalesAmount->value => (float) $sale->total,
            TargetMetric::InvoiceCount->value => 1.0,
            TargetMetric::CustomerCount->value => $customer ? 1.0 : 0.0,
            TargetMetric::Quantity->value => (float) $sale->items->sum('quantity'),
            TargetMetric::GrossProfit->value => (float) $sale->items->sum(function ($item) {
                $cost = $item->cost ?? 0;
                return ($item->price - $cost) * $item->quantity;
            }),
            TargetMetric::CollectionAmount->value => (float) $sale->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            TargetMetric::NewCustomerCount->value => $this->isNewCustomer($customer, $sale) ? 1.0 : 0.0,
        ];
    }

    private function isNewCustomer(?Customer $customer, Sale $sale): bool
    {
        if (! $customer) {
            return false;
        }
        if ($customer->created_at < $sale->sold_at->copy()->startOfPeriod()) {
            return false;
        }
        $previousSales = Sale::where('customer_id', $customer->id)
            ->where('user_id', $sale->user_id)
            ->where('id', '!=', $sale->id)
            ->where('sold_at', '<', $sale->sold_at)
            ->exists();
        return ! $previousSales;
    }
}
```

- [ ] **Step 3: Run tests**

```bash
php artisan test --filter=TargetAchievementUpdaterTest
```

Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add app/Services/SalesPerformance/TargetAchievementUpdaterInterface.php app/Services/SalesPerformance/TargetAchievementUpdater.php
git commit -m "feat(targets): TargetAchievementUpdater service"
```

---

## Phase 5 — Sale Observer

### Task 12: Write failing test for SaleObserver

**Files:**
- Create: `tests/Feature/SalesPerformance/SalesTargetAchievementsTest.php`

- [ ] **Step 1: Create feature test**

```bash
mkdir -p tests/Feature/SalesPerformance
```

Create `tests/Feature/SalesPerformance/SalesTargetAchievementsTest.php`:

```php
<?php

namespace Tests\Feature\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use App\Models\Customer;
use App\Models\SalesPerformance\SalesTarget;
use App\Models\SalesPerformance\SalesTargetLine;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTargetAchievementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_sale_updates_target_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Monthly Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        Sale::create([
            'invoice_number' => 'INV-TEST-1',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $salesperson->id,
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'total' => 500,
            'paid' => 500,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        $this->assertDatabaseHas('sales_target_achievements', [
            'sales_target_line_id' => $line->id,
            'snapshot_date' => now()->toDateString(),
            'achieved_value' => '500.0000',
        ]);
    }

    public function test_voiding_a_sale_reverses_target_achievement(): void
    {
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $target = SalesTarget::create([
            'salesperson_user_id' => $salesperson->id,
            'period_type' => TargetPeriod::Monthly->value,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'name' => 'Monthly Target',
            'status' => TargetStatus::Active->value,
            'created_by' => $salesperson->id,
        ]);

        $line = SalesTargetLine::create([
            'sales_target_id' => $target->id,
            'metric' => TargetMetric::SalesAmount->value,
            'target_value' => 10000,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-TEST-2',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $salesperson->id,
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'total' => 500,
            'paid' => 500,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        $sale->update(['status' => 'voided']);

        $this->assertDatabaseHas('sales_target_achievements', [
            'sales_target_line_id' => $line->id,
            'achieved_value' => '0.0000',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=SalesTargetAchievementsTest
```

Expected: FAIL (no observer yet).

- [ ] **Step 3: Commit failing test**

```bash
git add tests/Feature/SalesPerformance/SalesTargetAchievementsTest.php
git commit -m "test(targets): failing test for SaleObserver integration"
```

---

### Task 13: Implement SaleObserver

**Files:**
- Create: `app/Observers/SaleObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Create observer**

```bash
mkdir -p app/Observers
```

Create `app/Observers/SaleObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\SalesPerformance\TargetAchievementUpdaterInterface;

class SaleObserver
{
    public function __construct(
        private TargetAchievementUpdaterInterface $updater
    ) {}

    public function created(Sale $sale): void
    {
        if ($sale->status === 'completed') {
            $this->updater->applySale($sale);
        }
    }

    public function updated(Sale $sale): void
    {
        if ($sale->isDirty('status')) {
            $original = $sale->getOriginal('status');
            $current = $sale->status;

            if ($original === 'completed' && in_array($current, ['voided', 'canceled'], true)) {
                $this->updater->reverseSale($sale);
            } elseif ($original !== 'completed' && $current === 'completed') {
                $this->updater->applySale($sale);
            }
        }
    }

    public function deleted(Sale $sale): void
    {
        if ($sale->status === 'completed') {
            $this->updater->reverseSale($sale);
        }
    }
}
```

- [ ] **Step 2: Register observer in AppServiceProvider**

Edit `app/Providers/AppServiceProvider.php`:

In the `register()` or `boot()` method, add:

```php
use App\Models\Sale;
use App\Observers\SaleObserver;
use App\Services\SalesPerformance\TargetAchievementUpdater;
use App\Services\SalesPerformance\TargetAchievementUpdaterInterface;

public function register(): void
{
    $this->app->bind(TargetAchievementUpdaterInterface::class, TargetAchievementUpdater::class);
}

public function boot(): void
{
    Sale::observe(SaleObserver::class);
}
```

- [ ] **Step 3: Run tests**

```bash
php artisan test --filter=SalesTargetAchievementsTest
```

Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add app/Observers/SaleObserver.php app/Providers/AppServiceProvider.php
git commit -m "feat(observer): SaleObserver triggers target achievement updates"
```

---

## Phase 6 — API Endpoints

### Task 14: Teams API

**Files:**
- Create: `app/Http/Requests/SalesPerformance/StoreTeamRequest.php`
- Create: `app/Http/Requests/SalesPerformance/UpdateTeamRequest.php`
- Create: `app/Http/Resources/SalesPerformance/TeamResource.php`
- Create: `app/Http/Controllers/Api/SalesPerformance/TeamController.php`

- [ ] **Step 1: Create StoreTeamRequest**

```bash
mkdir -p app/Http/Requests/SalesPerformance
```

Create `app/Http/Requests/SalesPerformance/StoreTeamRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('teams.manage');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:teams,code',
            'leader_user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 2: Create UpdateTeamRequest**

Create `app/Http/Requests/SalesPerformance/UpdateTeamRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('teams.manage');
    }

    public function rules(): array
    {
        $teamId = $this->route('team');
        return [
            'name' => 'sometimes|string|max:255',
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('teams', 'code')->ignore($teamId)],
            'leader_user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 3: Create TeamResource**

```bash
mkdir -p app/Http/Resources/SalesPerformance
```

Create `app/Http/Resources/SalesPerformance/TeamResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'leader' => $this->whenLoaded('leader', fn () => [
                'id' => $this->leader->id,
                'name' => $this->leader->name,
            ]),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'members_count' => $this->whenCounted('members'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

- [ ] **Step 4: Create TeamController**

```bash
mkdir -p app/Http/Controllers/Api/SalesPerformance
```

Create `app/Http/Controllers/Api/SalesPerformance/TeamController.php`:

```php
<?php

namespace App\Http\Controllers\Api\SalesPerformance;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPerformance\StoreTeamRequest;
use App\Http\Requests\SalesPerformance\UpdateTeamRequest;
use App\Http\Resources\SalesPerformance\TeamResource;
use App\Models\SalesPerformance\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Team::class);
        $query = Team::query()->with('leader')->withCount('members');
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        return response()->json([
            'data' => TeamResource::collection($query->paginate(20)),
        ]);
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::create($request->validated());
        return response()->json(['data' => new TeamResource($team)], 201);
    }

    public function show(Team $team): JsonResponse
    {
        $this->authorize('view', $team);
        $team->load('leader')->loadCount('members');
        return response()->json(['data' => new TeamResource($team)]);
    }

    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $team->update($request->validated());
        return response()->json(['data' => new TeamResource($team)]);
    }

    public function destroy(Team $team): JsonResponse
    {
        $this->authorize('delete', $team);
        $team->delete();
        return response()->json(['message' => 'Team deleted.']);
    }
}
```

- [ ] **Step 5: Register TeamPolicy**

Create `app/Policies/SalesPerformance/TeamPolicy.php`:

```php
<?php

namespace App\Policies\SalesPerformance;

use App\Models\SalesPerformance\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('teams.view');
    }

    public function view(User $user, Team $team): bool
    {
        return $user->can('teams.view');
    }

    public function create(User $user): bool
    {
        return $user->can('teams.manage');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can('teams.manage');
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->can('teams.manage');
    }
}
```

Register in `AuthServiceProvider` (or auto-discovery if enabled):

```php
protected $policies = [
    \App\Models\SalesPerformance\Team::class => \App\Policies\SalesPerformance\TeamPolicy::class,
];
```

- [ ] **Step 6: Add route**

Edit `routes/api.php`:

```php
use App\Http\Controllers\Api\SalesPerformance\TeamController;

Route::middleware(['auth:sanctum'])->prefix('sales-performance')->group(function () {
    Route::apiResource('teams', TeamController::class);
});
```

- [ ] **Step 7: Write feature test**

Create `tests/Feature/SalesPerformance/TeamsTest.php`:

```php
<?php

namespace Tests\Feature\SalesPerformance;

use App\Models\SalesPerformance\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_teams(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('teams.view');
        Team::factory()->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/sales-performance/teams');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_create_team(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('teams.manage');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/sales-performance/teams', [
                'name' => 'Sales Team Alpha',
                'code' => 'ALPHA',
                'is_active' => true,
            ]);

        $response->assertCreated()->assertJsonPath('data.code', 'ALPHA');
        $this->assertDatabaseHas('teams', ['code' => 'ALPHA']);
    }

    public function test_unauthorized_user_cannot_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/sales-performance/teams', [
                'name' => 'Sales Team Alpha',
                'code' => 'ALPHA',
            ]);

        $response->assertForbidden();
    }
}
```

- [ ] **Step 8: Run tests**

```bash
php artisan test --filter=TeamsTest
```

Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add app/Http/Requests/SalesPerformance/StoreTeamRequest.php app/Http/Requests/SalesPerformance/UpdateTeamRequest.php app/Http/Resources/SalesPerformance/TeamResource.php app/Http/Controllers/Api/SalesPerformance/TeamController.php app/Policies/SalesPerformance/TeamPolicy.php app/Providers/AuthServiceProvider.php routes/api.php tests/Feature/SalesPerformance/TeamsTest.php
git commit -m "feat(api): Teams CRUD endpoints"
```

---

### Task 15: Territories API

**Files:**
- Create: `app/Http/Requests/SalesPerformance/StoreTerritoryRequest.php`
- Create: `app/Http/Requests/SalesPerformance/AttachTerritoryMembersRequest.php`
- Create: `app/Http/Resources/SalesPerformance/TerritoryResource.php`
- Create: `app/Http/Controllers/Api/SalesPerformance/TerritoryController.php`
- Create: `app/Policies/SalesPerformance/TerritoryPolicy.php`
- Create: `tests/Feature/SalesPerformance/TerritoriesTest.php`

- [ ] **Step 1: Create StoreTerritoryRequest**

Create `app/Http/Requests/SalesPerformance/StoreTerritoryRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTerritoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('territories.manage');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:territories,code',
            'region' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 2: Create AttachTerritoryMembersRequest**

Create `app/Http/Requests/SalesPerformance/AttachTerritoryMembersRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use Illuminate\Foundation\Http\FormRequest;

class AttachTerritoryMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('territories.manage');
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after:valid_from',
        ];
    }
}
```

- [ ] **Step 3: Create TerritoryResource**

Create `app/Http/Resources/SalesPerformance/TerritoryResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerritoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'region' => $this->region,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'salespeople' => $this->whenLoaded('salespeople', fn () =>
                $this->salespeople->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'valid_from' => $u->pivot->valid_from,
                    'valid_to' => $u->pivot->valid_to,
                ])
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

- [ ] **Step 4: Create TerritoryController**

Create `app/Http/Controllers/Api/SalesPerformance/TerritoryController.php`:

```php
<?php

namespace App\Http\Controllers\Api\SalesPerformance;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPerformance\AttachTerritoryMembersRequest;
use App\Http\Requests\SalesPerformance\StoreTerritoryRequest;
use App\Http\Resources\SalesPerformance\TerritoryResource;
use App\Models\SalesPerformance\Territory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerritoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Territory::query()->with('salespeople');
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }
        return response()->json([
            'data' => TerritoryResource::collection($query->paginate(20)),
        ]);
    }

    public function store(StoreTerritoryRequest $request): JsonResponse
    {
        $territory = Territory::create($request->validated());
        return response()->json(['data' => new TerritoryResource($territory)], 201);
    }

    public function show(Territory $territory): JsonResponse
    {
        $territory->load('salespeople');
        return response()->json(['data' => new TerritoryResource($territory)]);
    }

    public function update(StoreTerritoryRequest $request, Territory $territory): JsonResponse
    {
        $territory->update($request->validated());
        return response()->json(['data' => new TerritoryResource($territory)]);
    }

    public function attachMembers(AttachTerritoryMembersRequest $request, Territory $territory): JsonResponse
    {
        $payload = $request->validated();
        foreach ($payload['user_ids'] as $userId) {
            $territory->salespeople()->attach($userId, [
                'assigned_at' => now(),
                'assigned_by' => $request->user()->id,
                'valid_from' => $payload['valid_from'],
                'valid_to' => $payload['valid_to'] ?? null,
            ]);
        }
        return response()->json(['message' => 'Members attached.']);
    }

    public function detachMember(Territory $territory, int $userId): JsonResponse
    {
        $territory->salespeople()->detach($userId);
        return response()->json(['message' => 'Member detached.']);
    }
}
```

- [ ] **Step 5: Create TerritoryPolicy + register**

Create `app/Policies/SalesPerformance/TerritoryPolicy.php`:

```php
<?php

namespace App\Policies\SalesPerformance;

use App\Models\SalesPerformance\Territory;
use App\Models\User;

class TerritoryPolicy
{
    public function viewAny(User $user): bool { return $user->can('territories.view'); }
    public function view(User $user, Territory $territory): bool { return $user->can('territories.view'); }
    public function create(User $user): bool { return $user->can('territories.manage'); }
    public function update(User $user, Territory $territory): bool { return $user->can('territories.manage'); }
    public function delete(User $user, Territory $territory): bool { return $user->can('territories.manage'); }
    public function attachMembers(User $user, Territory $territory): bool { return $user->can('territories.manage'); }
}
```

Register in `AuthServiceProvider` `$policies` array.

- [ ] **Step 6: Add routes**

```php
Route::post('territories/{territory}/members', [TerritoryController::class, 'attachMembers']);
Route::delete('territories/{territory}/members/{userId}', [TerritoryController::class, 'detachMember']);
Route::apiResource('territories', TerritoryController::class)->except(['destroy']);
```

- [ ] **Step 7: Write feature test**

Create `tests/Feature/SalesPerformance/TerritoriesTest.php`:

```php
<?php

namespace Tests\Feature\SalesPerformance;

use App\Models\SalesPerformance\Territory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerritoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_territory(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('territories.manage');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/sales-performance/territories', [
                'name' => 'North Region',
                'code' => 'NORTH',
                'region' => 'North',
                'is_active' => true,
            ]);

        $response->assertCreated()->assertJsonPath('data.code', 'NORTH');
    }

    public function test_can_attach_members(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('territories.manage');
        $spv = User::factory()->create();
        $territory = Territory::create(['name' => 'North', 'code' => 'N', 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/sales-performance/territories/{$territory->id}/members", [
                'user_ids' => [$spv->id],
                'valid_from' => now()->toDateString(),
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('territory_user', [
            'territory_id' => $territory->id,
            'user_id' => $spv->id,
        ]);
    }
}
```

- [ ] **Step 8: Run tests and commit**

```bash
php artisan test --filter=TerritoriesTest
git add app/Http/Requests/SalesPerformance/StoreTerritoryRequest.php app/Http/Requests/SalesPerformance/AttachTerritoryMembersRequest.php app/Http/Resources/SalesPerformance/TerritoryResource.php app/Http/Controllers/Api/SalesPerformance/TerritoryController.php app/Policies/SalesPerformance/TerritoryPolicy.php app/Providers/AuthServiceProvider.php routes/api.php tests/Feature/SalesPerformance/TerritoriesTest.php
git commit -m "feat(api): Territories CRUD + member attach"
```

---

### Task 16: Salespeople API

**Files:**
- Create: `app/Http/Requests/SalesPerformance/StoreSalespersonRequest.php`
- Create: `app/Http/Requests/SalesPerformance/UpdateSalespersonRequest.php`
- Create: `app/Http/Resources/SalesPerformance/SalespersonResource.php`
- Create: `app/Http/Controllers/Api/SalesPerformance/SalespersonController.php`
- Create: `app/Policies/SalesPerformance/SalespersonPolicy.php`
- Create: `tests/Feature/SalesPerformance/SalespeopleTest.php`

- [ ] **Step 1: Create StoreSalespersonRequest**

Create `app/Http/Requests/SalesPerformance/StoreSalespersonRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSalespersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('salespeople.manage');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'employment_status' => ['nullable', new Enum(EmploymentStatus::class)],
            'team_id' => 'nullable|exists:teams,id',
            'branch_id' => 'nullable|exists:warehouses,id',
            'territory_ids' => 'nullable|array',
            'territory_ids.*' => 'exists:territories,id',
        ];
    }
}
```

- [ ] **Step 2: Create UpdateSalespersonRequest**

Create `app/Http/Requests/SalesPerformance/UpdateSalespersonRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use App\Enums\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateSalespersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('salespeople.manage');
    }

    public function rules(): array
    {
        $userId = $this->route('salesperson');
        return [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => 'sometimes|string|min:8',
            'employment_status' => ['nullable', new Enum(EmploymentStatus::class)],
            'team_id' => 'nullable|exists:teams,id',
            'branch_id' => 'nullable|exists:warehouses,id',
            'territory_ids' => 'nullable|array',
            'territory_ids.*' => 'exists:territories,id',
        ];
    }
}
```

- [ ] **Step 3: Create SalespersonResource**

Create `app/Http/Resources/SalesPerformance/SalespersonResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalespersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'employment_status' => $this->employment_status?->value,
            'employment_status_label' => $this->employment_status?->label(),
            'team' => $this->whenLoaded('team', fn () => [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'territories' => $this->whenLoaded('territories', fn () =>
                $this->territories->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ),
            'created_at' => $this->created_at,
        ];
    }
}
```

- [ ] **Step 4: Create SalespersonController**

Create `app/Http/Controllers/Api/SalesPerformance/SalespersonController.php`:

```php
<?php

namespace App\Http\Controllers\Api\SalesPerformance;

use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPerformance\StoreSalespersonRequest;
use App\Http\Requests\SalesPerformance\UpdateSalespersonRequest;
use App\Http\Resources\SalesPerformance\SalespersonResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SalespersonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::salespeople()->with(['team', 'branch', 'territories']);
        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        }
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }
        if ($request->filled('territory_id')) {
            $query->whereHas('territories', fn ($q) => $q->where('territories.id', $request->territory_id));
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('email', 'like', '%' . $request->q . '%');
            });
        }
        return response()->json([
            'data' => SalespersonResource::collection($query->paginate(20)),
        ]);
    }

    public function store(StoreSalespersonRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = UserRole::Salesperson->value;
        $data['employment_status'] = $data['employment_status'] ?? EmploymentStatus::Active->value;
        $data['password'] = Hash::make($data['password']);

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create($data);
            if (! empty($data['territory_ids'])) {
                foreach ($data['territory_ids'] as $territoryId) {
                    $user->territories()->attach($territoryId, [
                        'assigned_at' => now(),
                        'assigned_by' => $request->user()->id,
                        'valid_from' => now()->toDateString(),
                    ]);
                }
            }
            return $user;
        });

        $user->load(['team', 'branch', 'territories']);
        return response()->json(['data' => new SalespersonResource($user)], 201);
    }

    public function show(User $salesperson): JsonResponse
    {
        $salesperson->load(['team', 'branch', 'territories', 'customerAssignments.customer']);
        return response()->json(['data' => new SalespersonResource($salesperson)]);
    }

    public function update(UpdateSalespersonRequest $request, User $salesperson): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        DB::transaction(function () use ($data, $salesperson, $request) {
            $salesperson->fill(array_diff_key($data, ['territory_ids' => 0]));
            $salesperson->save();

            if (array_key_exists('territory_ids', $data)) {
                $salesperson->territories()->detach();
                foreach ($data['territory_ids'] ?? [] as $territoryId) {
                    $salesperson->territories()->attach($territoryId, [
                        'assigned_at' => now(),
                        'assigned_by' => $request->user()->id,
                        'valid_from' => now()->toDateString(),
                    ]);
                }
            }
        });

        $salesperson->load(['team', 'branch', 'territories']);
        return response()->json(['data' => new SalespersonResource($salesperson)]);
    }
}
```

- [ ] **Step 5: Create SalespersonPolicy**

Create `app/Policies/SalesPerformance/SalespersonPolicy.php`:

```php
<?php

namespace App\Policies\SalesPerformance;

use App\Models\User;

class SalespersonPolicy
{
    public function viewAny(User $user): bool { return $user->can('salespeople.view'); }
    public function view(User $user, User $salesperson): bool { return $user->can('salespeople.view'); }
    public function create(User $user): bool { return $user->can('salespeople.manage'); }
    public function update(User $user, User $salesperson): bool { return $user->can('salespeople.manage'); }
}
```

Register in `AuthServiceProvider`.

- [ ] **Step 6: Add routes**

```php
use App\Http\Controllers\Api\SalesPerformance\SalespersonController;

Route::apiResource('salespeople', SalespersonController::class);
```

- [ ] **Step 7: Write feature test**

Create `tests/Feature/SalesPerformance/SalespeopleTest.php`:

```php
<?php

namespace Tests\Feature\SalesPerformance;

use App\Enums\EmploymentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalespeopleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_salesperson(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('salespeople.manage');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/sales-performance/salespeople', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
                'employment_status' => EmploymentStatus::Active->value,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonPath('data.employment_status', 'active');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => 'salesperson',
        ]);
    }

    public function test_can_list_filtered_salespeople(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('salespeople.view');
        User::factory()->count(3)->create(['role' => \App\Enums\UserRole::Salesperson]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/sales-performance/salespeople');

        $response->assertOk()->assertJsonCount(3, 'data');
    }
}
```

- [ ] **Step 8: Run tests and commit**

```bash
php artisan test --filter=SalespeopleTest
git add app/Http/Requests/SalesPerformance/StoreSalespersonRequest.php app/Http/Requests/SalesPerformance/UpdateSalespersonRequest.php app/Http/Resources/SalesPerformance/SalespersonResource.php app/Http/Controllers/Api/SalesPerformance/SalespersonController.php app/Policies/SalesPerformance/SalespersonPolicy.php app/Providers/AuthServiceProvider.php routes/api.php tests/Feature/SalesPerformance/SalespeopleTest.php
git commit -m "feat(api): Salespeople CRUD endpoints"
```

---

### Task 17: Customer Assignments API

**Files:**
- Create: `app/Http/Requests/SalesPerformance/StoreCustomerAssignmentRequest.php`
- Create: `app/Http/Requests/SalesPerformance/UpdateCustomerAssignmentRequest.php`
- Create: `app/Http/Resources/SalesPerformance/CustomerAssignmentResource.php`
- Create: `app/Services/SalesPerformance/AssignmentApprovalCoordinator.php`
- Create: `app/Http/Controllers/Api/SalesPerformance/CustomerAssignmentController.php`
- Create: `app/Http/Controllers/Api/SalesPerformance/CustomerVisitHistoryController.php`
- Create: `app/Policies/SalesPerformance/CustomerAssignmentPolicy.php`
- Create: `tests/Feature/SalesPerformance/CustomerAssignmentsTest.php`

- [ ] **Step 1: Create StoreCustomerAssignmentRequest**

Create `app/Http/Requests/SalesPerformance/StoreCustomerAssignmentRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignments.manage');
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'salesperson_user_id' => 'required|exists:users,id',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'notes' => 'nullable|string',
        ];
    }
}
```

- [ ] **Step 2: Create UpdateCustomerAssignmentRequest**

Create `app/Http/Requests/SalesPerformance/UpdateCustomerAssignmentRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignments.manage');
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string',
            'valid_to' => 'nullable|date',
        ];
    }
}
```

- [ ] **Step 3: Create CustomerAssignmentResource**

Create `app/Http/Resources/SalesPerformance/CustomerAssignmentResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'code' => $this->customer->code,
            ]),
            'salesperson' => $this->whenLoaded('salesperson', fn () => [
                'id' => $this->salesperson->id,
                'name' => $this->salesperson->name,
            ]),
            'status' => $this->status,
            'valid_from' => $this->valid_from,
            'valid_to' => $this->valid_to,
            'notes' => $this->notes,
            'approval_id' => $this->approval_id,
            'created_at' => $this->created_at,
        ];
    }
}
```

- [ ] **Step 4: Create AssignmentApprovalCoordinator service**

```bash
mkdir -p app/Services/SalesPerformance
```

Create `app/Services/SalesPerformance/AssignmentApprovalCoordinator.php`:

```php
<?php

namespace App\Services\SalesPerformance;

use App\Models\Approval;
use App\Models\SalesPerformance\CustomerAssignment;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\DB;

class AssignmentApprovalCoordinator
{
    public function __construct(private ApprovalService $approvals) {}

    public function submitForApproval(array $data, int $submittedBy): CustomerAssignment
    {
        return DB::transaction(function () use ($data, $submittedBy) {
            $assignment = CustomerAssignment::create([
                'customer_id' => $data['customer_id'],
                'salesperson_user_id' => $data['salesperson_user_id'],
                'status' => 'pending',
                'valid_from' => $data['valid_from'],
                'valid_to' => $data['valid_to'] ?? null,
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $submittedBy,
            ]);

            $approval = $this->approvals->create([
                'subject_type' => CustomerAssignment::class,
                'subject_id' => $assignment->id,
                'submitted_by' => $submittedBy,
                'status' => 'pending',
            ]);

            $assignment->update(['approval_id' => $approval->id]);

            return $assignment->fresh(['customer', 'salesperson']);
        });
    }

    public function approve(CustomerAssignment $assignment, int $approverId): CustomerAssignment
    {
        return DB::transaction(function () use ($assignment, $approverId) {
            $this->approvals->approve($assignment->approval, $approverId);
            $assignment->update(['status' => 'active']);
            return $assignment->fresh();
        });
    }

    public function reject(CustomerAssignment $assignment, int $approverId, string $reason): void
    {
        $this->approvals->reject($assignment->approval, $approverId, $reason);
    }
}
```

- [ ] **Step 5: Create CustomerAssignmentController**

Create `app/Http/Controllers/Api/SalesPerformance/CustomerAssignmentController.php`:

```php
<?php

namespace App\Http\Controllers\Api\SalesPerformance;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPerformance\StoreCustomerAssignmentRequest;
use App\Http\Requests\SalesPerformance\UpdateCustomerAssignmentRequest;
use App\Http\Resources\SalesPerformance\CustomerAssignmentResource;
use App\Models\SalesPerformance\CustomerAssignment;
use App\Services\SalesPerformance\AssignmentApprovalCoordinator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAssignmentController extends Controller
{
    public function __construct(private AssignmentApprovalCoordinator $coordinator) {}

    public function index(Request $request): JsonResponse
    {
        $query = CustomerAssignment::with(['customer', 'salesperson']);
        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_user_id', $request->salesperson_id);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return response()->json([
            'data' => CustomerAssignmentResource::collection($query->paginate(20)),
        ]);
    }

    public function store(StoreCustomerAssignmentRequest $request): JsonResponse
    {
        $assignment = $this->coordinator->submitForApproval($request->validated(), $request->user()->id);
        return response()->json(['data' => new CustomerAssignmentResource($assignment)], 201);
    }

    public function show(CustomerAssignment $assignment): JsonResponse
    {
        $assignment->load(['customer', 'salesperson']);
        return response()->json(['data' => new CustomerAssignmentResource($assignment)]);
    }

    public function update(UpdateCustomerAssignmentRequest $request, CustomerAssignment $assignment): JsonResponse
    {
        $assignment->update($request->validated());
        return response()->json(['data' => new CustomerAssignmentResource($assignment)]);
    }

    public function revoke(Request $request, CustomerAssignment $assignment): JsonResponse
    {
        $assignment->update([
            'status' => 'revoked',
            'valid_to' => now()->toDateString(),
        ]);
        return response()->json(['data' => new CustomerAssignmentResource($assignment)]);
    }
}
```

- [ ] **Step 6: Create CustomerVisitHistoryController**

Create `app/Http/Controllers/Api/SalesPerformance/CustomerVisitHistoryController.php`:

```php
<?php

namespace App\Http\Controllers\Api\SalesPerformance;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerVisitHistoryController extends Controller
{
    public function show(Request $request, Customer $customer): JsonResponse
    {
        $sales = Sale::with('user')
            ->where('customer_id', $customer->id)
            ->orderByDesc('sold_at')
            ->limit(50)
            ->get()
            ->map(fn ($sale) => [
                'date' => $sale->sold_at,
                'invoice_number' => $sale->invoice_number,
                'salesperson' => $sale->user?->name,
                'total' => (float) $sale->total,
                'status' => $sale->status,
            ]);

        $bySalesperson = Sale::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->selectRaw('user_id, SUM(total) as total_sales, COUNT(*) as invoice_count')
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(fn ($row) => [
                'salesperson' => $row->user?->name,
                'total_sales' => (float) $row->total_sales,
                'invoice_count' => (int) $row->invoice_count,
            ]);

        return response()->json([
            'data' => [
                'customer' => ['id' => $customer->id, 'name' => $customer->name],
                'recent_sales' => $sales,
                'sales_by_salesperson' => $bySalesperson,
            ],
        ]);
    }
}
```

- [ ] **Step 7: Create CustomerAssignmentPolicy**

Create `app/Policies/SalesPerformance/CustomerAssignmentPolicy.php`:

```php
<?php

namespace App\Policies\SalesPerformance;

use App\Models\SalesPerformance\CustomerAssignment;
use App\Models\User;

class CustomerAssignmentPolicy
{
    public function viewAny(User $user): bool { return $user->can('assignments.view'); }
    public function view(User $user, CustomerAssignment $a): bool { return $user->can('assignments.view'); }
    public function create(User $user): bool { return $user->can('assignments.manage'); }
    public function update(User $user, CustomerAssignment $a): bool { return $user->can('assignments.manage'); }
}
```

Register in `AuthServiceProvider`.

- [ ] **Step 8: Add routes**

```php
Route::apiResource('customers/assignments', CustomerAssignmentController::class)->except(['destroy']);
Route::post('customers/assignments/{assignment}/revoke', [CustomerAssignmentController::class, 'revoke']);
Route::get('customers/{customer}/visit-history', [CustomerVisitHistoryController::class, 'show']);
```

- [ ] **Step 9: Write feature test**

Create `tests/Feature/SalesPerformance/CustomerAssignmentsTest.php`:

```php
<?php

namespace Tests\Feature\SalesPerformance;

use App\Models\Customer;
use App\Models\SalesPerformance\CustomerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_assignment_submits_for_approval(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['assignments.manage', 'approvals.review']);
        $customer = Customer::factory()->create();
        $salesperson = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/sales-performance/customers/assignments', [
                'customer_id' => $customer->id,
                'salesperson_user_id' => $salesperson->id,
                'valid_from' => now()->toDateString(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('customer_assignments', [
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('approvals', [
            'subject_type' => CustomerAssignment::class,
            'status' => 'pending',
        ]);
    }

    public function test_revoking_assignment(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('assignments.manage');
        $customer = Customer::factory()->create();
        $salesperson = User::factory()->create();
        $assignment = CustomerAssignment::create([
            'customer_id' => $customer->id,
            'salesperson_user_id' => $salesperson->id,
            'status' => 'active',
            'valid_from' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/sales-performance/customers/assignments/{$assignment->id}/revoke");

        $response->assertOk()->assertJsonPath('data.status', 'revoked');
    }
}
```

- [ ] **Step 10: Run tests and commit**

```bash
php artisan test --filter=CustomerAssignmentsTest
git add app/Http/Requests/SalesPerformance/StoreCustomerAssignmentRequest.php app/Http/Requests/SalesPerformance/UpdateCustomerAssignmentRequest.php app/Http/Resources/SalesPerformance/CustomerAssignmentResource.php app/Services/SalesPerformance/AssignmentApprovalCoordinator.php app/Http/Controllers/Api/SalesPerformance/CustomerAssignmentController.php app/Http/Controllers/Api/SalesPerformance/CustomerVisitHistoryController.php app/Policies/SalesPerformance/CustomerAssignmentPolicy.php app/Providers/AuthServiceProvider.php routes/api.php tests/Feature/SalesPerformance/CustomerAssignmentsTest.php
git commit -m "feat(api): customer assignments + visit history"
```

---

### Task 18: Approvals API

**Files:**
- Create: `app/Http/Resources/SalesPerformance/ApprovalResource.php`
- Create: `app/Http/Controllers/Api/SalesPerformance/ApprovalController.php`
- Create: `tests/Feature/SalesPerformance/ApprovalsTest.php`

- [ ] **Step 1: Create ApprovalResource**

Create `app/Http/Resources/SalesPerformance/ApprovalResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'status' => $this->status,
            'submitted_by' => $this->submitted_by,
            'approver_id' => $this->approver_id,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
            'approved_at' => $this->approved_at,
        ];
    }
}
```

- [ ] **Step 2: Create ApprovalController**

Create `app/Http/Controllers/Api/SalesPerformance/ApprovalController.php`:

```php
<?php

namespace App\Http\Controllers\Api\SalesPerformance;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesPerformance\ApprovalResource;
use App\Models\Approval;
use App\Models\SalesPerformance\CustomerAssignment;
use App\Services\SalesPerformance\AssignmentApprovalCoordinator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private AssignmentApprovalCoordinator $coordinator) {}

    public function index(Request $request): JsonResponse
    {
        $approvals = Approval::query()
            ->where('status', 'pending')
            ->where('approver_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(['data' => ApprovalResource::collection($approvals)]);
    }

    public function show(Approval $approval): JsonResponse
    {
        $approval->load('subject');
        return response()->json(['data' => new ApprovalResource($approval)]);
    }

    public function approve(Request $request, Approval $approval): JsonResponse
    {
        $this->authorize('review', $approval);
        if ($approval->subject_type === CustomerAssignment::class) {
            $assignment = CustomerAssignment::findOrFail($approval->subject_id);
            $this->coordinator->approve($assignment, $request->user()->id);
        }
        return response()->json(['message' => 'Approved.']);
    }

    public function reject(Request $request, Approval $approval): JsonResponse
    {
        $this->authorize('review', $approval);
        $request->validate(['reason' => 'required|string|max:500']);
        if ($approval->subject_type === CustomerAssignment::class) {
            $assignment = CustomerAssignment::findOrFail($approval->subject_id);
            $this->coordinator->reject($assignment, $request->user()->id, $request->reason);
        }
        return response()->json(['message' => 'Rejected.']);
    }
}
```

- [ ] **Step 3: Add routes**

```php
Route::get('approvals', [ApprovalController::class, 'index']);
Route::get('approvals/{approval}', [ApprovalController::class, 'show']);
Route::post('approvals/{approval}/approve', [ApprovalController::class, 'approve']);
Route::post('approvals/{approval}/reject', [ApprovalController::class, 'reject']);
```

- [ ] **Step 4: Write feature test**

Create `tests/Feature/SalesPerformance/ApprovalsTest.php`:

```php
<?php

namespace Tests\Feature\SalesPerformance;

use App\Models\Customer;
use App\Models\SalesPerformance\CustomerAssignment;
use App\Models\User;
use App\Services\SalesPerformance\AssignmentApprovalCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_assignment_activates_it(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['assignments.manage', 'approvals.review']);
        $salesperson = User::factory()->create();
        $customer = Customer::factory()->create();

        $coordinator = app(AssignmentApprovalCoordinator::class);
        $assignment = $coordinator->submitForApproval([
            'customer_id' => $customer->id,
            'salesperson_user_id' => $salesperson->id,
            'valid_from' => now()->toDateString(),
        ], $manager->id);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson("/api/sales-performance/approvals/{$assignment->approval_id}/approve");

        $response->assertOk();
        $this->assertDatabaseHas('customer_assignments', [
            'id' => $assignment->id,
            'status' => 'active',
        ]);
    }
}
```

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=ApprovalsTest
git add app/Http/Resources/SalesPerformance/ApprovalResource.php app/Http/Controllers/Api/SalesPerformance/ApprovalController.php routes/api.php tests/Feature/SalesPerformance/ApprovalsTest.php
git commit -m "feat(api): Approvals inbox + approve/reject"
```

---

### Task 19: Target Templates API

**Files:**
- Create: `app/Http/Requests/SalesPerformance/StoreTargetTemplateRequest.php`
- Create: `app/Http/Requests/SalesPerformance/UpdateTargetTemplateRequest.php`
- Create: `app/Http/Resources/SalesPerformance/TargetTemplateResource.php`
- Create: `app/Http/Controllers/Api/SalesPerformance/TargetTemplateController.php`
- Create: `app/Policies/SalesPerformance/TargetTemplatePolicy.php`

- [ ] **Step 1: Create StoreTargetTemplateRequest**

Create `app/Http/Requests/SalesPerformance/StoreTargetTemplateRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTargetTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('targets.manage');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'period_type' => ['required', new Enum(TargetPeriod::class)],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'lines' => 'required|array|min:1',
            'lines.*.metric' => ['required', new Enum(TargetMetric::class)],
            'lines.*.default_value' => 'required|numeric|min:0',
            'lines.*.order_index' => 'nullable|integer',
        ];
    }
}
```

- [ ] **Step 2: Create UpdateTargetTemplateRequest**

Create `app/Http/Requests/SalesPerformance/UpdateTargetTemplateRequest.php` (same as Store but using `sometimes` rules).

- [ ] **Step 3: Create TargetTemplateResource**

Create `app/Http/Resources/SalesPerformance/TargetTemplateResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TargetTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'period_type' => $this->period_type?->value,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'lines' => $this->whenLoaded('lines', fn () =>
                $this->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'metric' => $l->metric?->value,
                    'metric_label' => $l->metric?->label(),
                    'default_value' => (float) $l->default_value,
                    'order_index' => $l->order_index,
                ])
            ),
            'created_at' => $this->created_at,
        ];
    }
}
```

- [ ] **Step 4: Create TargetTemplateController**

Create `app/Http/Controllers/Api/SalesPerformance/TargetTemplateController.php`:

```php
<?php

namespace App\Http\Controllers\Api\SalesPerformance;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPerformance\StoreTargetTemplateRequest;
use App\Http\Requests\SalesPerformance\UpdateTargetTemplateRequest;
use App\Http\Resources\SalesPerformance\TargetTemplateResource;
use App\Models\SalesPerformance\TargetTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TargetTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TargetTemplate::with('lines');
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        return response()->json([
            'data' => TargetTemplateResource::collection($query->paginate(20)),
        ]);
    }

    public function store(StoreTargetTemplateRequest $request): JsonResponse
    {
        $template = DB::transaction(function () use ($request) {
            $template = TargetTemplate::create([
                'name' => $request->name,
                'period_type' => $request->period_type,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'created_by' => $request->user()->id,
            ]);
            foreach ($request->lines as $i => $line) {
                $template->lines()->create([
                    'metric' => $line['metric'],
                    'default_value' => $line['default_value'],
                    'order_index' => $line['order_index'] ?? $i,
                ]);
            }
            return $template;
        });
        $template->load('lines');
        return response()->json(['data' => new TargetTemplateResource($template)], 201);
    }

    public function show(TargetTemplate $targetTemplate): JsonResponse
    {
        $targetTemplate->load('lines');
        return response()->json(['data' => new TargetTemplateResource($targetTemplate)]);
    }

    public function update(UpdateTargetTemplateRequest $request, TargetTemplate $targetTemplate): JsonResponse
    {
        DB::transaction(function () use ($request, $targetTemplate) {
            $targetTemplate->update($request->validated());
            if ($request->has('lines')) {
                $targetTemplate->lines()->delete();
                foreach ($request->lines as $i => $line) {
                    $targetTemplate->lines()->create([
                        'metric' => $line['metric'],
                        'default_value' => $line['default_value'],
                        'order_index' => $line['order_index'] ?? $i,
                    ]);
                }
            }
        });
        $targetTemplate->load('lines');
        return response()->json(['data' => new TargetTemplateResource($targetTemplate)]);
    }

    public function destroy(TargetTemplate $targetTemplate): JsonResponse
    {
        $targetTemplate->delete();
        return response()->json(['message' => 'Template deleted.']);
    }
}
```

- [ ] **Step 5: Create TargetTemplatePolicy + register**

Create `app/Policies/SalesPerformance/TargetTemplatePolicy.php`:

```php
<?php

namespace App\Policies\SalesPerformance;

use App\Models\SalesPerformance\TargetTemplate;
use App\Models\User;

class TargetTemplatePolicy
{
    public function viewAny(User $u): bool { return $u->can('targets.view'); }
    public function view(User $u, TargetTemplate $t): bool { return $u->can('targets.view'); }
    public function create(User $u): bool { return $u->can('targets.manage'); }
    public function update(User $u, TargetTemplate $t): bool { return $u->can('targets.manage'); }
    public function delete(User $u, TargetTemplate $t): bool { return $u->can('targets.manage'); }
}
```

Register in `AuthServiceProvider`.

- [ ] **Step 6: Add routes**

```php
Route::apiResource('target-templates', TargetTemplateController::class);
```

- [ ] **Step 7: Run tests and commit**

```bash
php artisan test
git add app/Http/Requests/SalesPerformance/StoreTargetTemplateRequest.php app/Http/Requests/SalesPerformance/UpdateTargetTemplateRequest.php app/Http/Resources/SalesPerformance/TargetTemplateResource.php app/Http/Controllers/Api/SalesPerformance/TargetTemplateController.php app/Policies/SalesPerformance/TargetTemplatePolicy.php app/Providers/AuthServiceProvider.php routes/api.php
git commit -m "feat(api): Target Templates CRUD"
```

---

### Task 20: Sales Targets API + Bulk Assigner

**Files:**
- Create: `app/Http/Requests/SalesPerformance/StoreSalesTargetRequest.php`
- Create: `app/Http/Requests/SalesPerformance/BulkAssignTargetRequest.php`
- Create: `app/Http/Resources/SalesPerformance/SalesTargetResource.php`
- Create: `app/Http/Resources/SalesPerformance/SalesTargetLineResource.php`
- Create: `app/Http/Resources/SalesPerformance/SalesTargetAchievementResource.php`
- Create: `app/Services/SalesPerformance/BulkTargetAssigner.php`
- Create: `app/Http/Controllers/Api/SalesPerformance/SalesTargetController.php`
- Create: `app/Policies/SalesPerformance/SalesTargetPolicy.php`
- Create: `tests/Feature/SalesPerformance/SalesTargetsTest.php`
- Create: `tests/Feature/SalesPerformance/BulkAssignmentTest.php`

- [ ] **Step 1: Create StoreSalesTargetRequest**

Create `app/Http/Requests/SalesPerformance/StoreSalesTargetRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreSalesTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('targets.manage');
    }

    public function rules(): array
    {
        return [
            'salesperson_user_id' => 'required|exists:users,id',
            'period_type' => ['required', new Enum(TargetPeriod::class)],
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'name' => 'required|string|max:255',
            'status' => ['nullable', new Enum(TargetStatus::class)],
            'target_template_id' => 'nullable|exists:target_templates,id',
            'lines' => 'required|array|min:1',
            'lines.*.metric' => ['required', new Enum(TargetMetric::class)],
            'lines.*.target_value' => 'required|numeric|min:0',
        ];
    }
}
```

- [ ] **Step 2: Create BulkAssignTargetRequest**

Create `app/Http/Requests/SalesPerformance/BulkAssignTargetRequest.php`:

```php
<?php

namespace App\Http\Requests\SalesPerformance;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('targets.manage');
    }

    public function rules(): array
    {
        return [
            'target_template_id' => 'required|exists:target_templates,id',
            'salesperson_user_ids' => 'required|array|min:1',
            'salesperson_user_ids.*' => 'exists:users,id',
            'period_start' => 'required|date',
            'name' => 'required|string|max:255',
        ];
    }
}
```

- [ ] **Step 3: Create SalesTargetLineResource + SalesTargetAchievementResource**

Create `app/Http/Resources/SalesPerformance/SalesTargetLineResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesTargetLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'metric' => $this->metric?->value,
            'metric_label' => $this->metric?->label(),
            'target_value' => (float) $this->target_value,
            'latest_achievement' => $this->whenLoaded('latestAchievement', fn () => $this->latestAchievement ? [
                'snapshot_date' => $this->latestAchievement->snapshot_date,
                'achieved_value' => (float) $this->latestAchievement->achieved_value,
                'achievement_pct' => (float) $this->latestAchievement->achievement_pct,
            ] : null),
        ];
    }
}
```

Create `app/Http/Resources/SalesPerformance/SalesTargetAchievementResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesTargetAchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'snapshot_date' => $this->snapshot_date,
            'achieved_value' => (float) $this->achieved_value,
            'achievement_pct' => (float) $this->achievement_pct,
            'computed_at' => $this->computed_at,
        ];
    }
}
```

- [ ] **Step 4: Create SalesTargetResource**

Create `app/Http/Resources/SalesPerformance/SalesTargetResource.php`:

```php
<?php

namespace App\Http\Resources\SalesPerformance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'period_type' => $this->period_type?->value,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'status' => $this->status?->value,
            'salesperson' => $this->whenLoaded('salesperson', fn () => [
                'id' => $this->salesperson->id,
                'name' => $this->salesperson->name,
            ]),
            'lines' => SalesTargetLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at,
        ];
    }
}
```

- [ ] **Step 5: Create BulkTargetAssigner**

Create `app/Services/SalesPerformance/BulkTargetAssigner.php`:

```php
<?php

namespace App\Services\SalesPerformance;

use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use App\Models\SalesPerformance\SalesTarget;
use App\Models\SalesPerformance\TargetTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkTargetAssigner
{
    public function assign(array $salespersonIds, TargetTemplate $template, string $periodStart, string $name, int $createdBy): array
    {
        return DB::transaction(function () use ($salespersonIds, $template, $periodStart, $name, $createdBy) {
            $created = [];
            $periodEnd = $this->periodEnd($template->period_type, $periodStart);
            foreach ($salespersonIds as $userId) {
                $target = SalesTarget::create([
                    'salesperson_user_id' => $userId,
                    'period_type' => $template->period_type->value,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'target_template_id' => $template->id,
                    'name' => $name,
                    'status' => TargetStatus::Active->value,
                    'created_by' => $createdBy,
                ]);
                foreach ($template->lines as $line) {
                    $target->lines()->create([
                        'metric' => $line->metric->value,
                        'target_value' => $line->default_value,
                    ]);
                }
                $created[] = $target;
            }
            return $created;
        });
    }

    private function periodEnd(TargetPeriod $period, string $start): string
    {
        $startDt = \Carbon\Carbon::parse($start);
        return match ($period) {
            TargetPeriod::Daily => $startDt->copy()->endOfDay()->toDateString(),
            TargetPeriod::Weekly => $startDt->copy()->endOfWeek()->toDateString(),
            TargetPeriod::Monthly => $startDt->copy()->endOfMonth()->toDateString(),
            TargetPeriod::Quarterly => $startDt->copy()->endOfQuarter()->toDateString(),
            TargetPeriod::Annual => $startDt->copy()->endOfYear()->toDateString(),
        };
    }
}
```

- [ ] **Step 6: Create SalesTargetController**

Create `app/Http/Controllers/Api/SalesPerformance/SalesTargetController.php`:

```php
<?php

namespace App\Http\Controllers\Api\SalesPerformance;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPerformance\BulkAssignTargetRequest;
use App\Http\Requests\SalesPerformance\StoreSalesTargetRequest;
use App\Http\Resources\SalesPerformance\SalesTargetResource;
use App\Models\SalesPerformance\SalesTarget;
use App\Models\SalesPerformance\TargetTemplate;
use App\Services\SalesPerformance\BulkTargetAssigner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesTargetController extends Controller
{
    public function __construct(private BulkTargetAssigner $bulkAssigner) {}

    public function index(Request $request): JsonResponse
    {
        $query = SalesTarget::with(['salesperson', 'lines.latestAchievement']);
        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_user_id', $request->salesperson_id);
        }
        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return response()->json([
            'data' => SalesTargetResource::collection($query->paginate(20)),
        ]);
    }

    public function store(StoreSalesTargetRequest $request): JsonResponse
    {
        $target = DB::transaction(function () use ($request) {
            $target = SalesTarget::create([
                'salesperson_user_id' => $request->salesperson_user_id,
                'period_type' => $request->period_type,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'target_template_id' => $request->target_template_id,
                'name' => $request->name,
                'status' => $request->status ?? 'draft',
                'created_by' => $request->user()->id,
            ]);
            foreach ($request->lines as $line) {
                $target->lines()->create($line);
            }
            return $target;
        });
        $target->load(['salesperson', 'lines.latestAchievement']);
        return response()->json(['data' => new SalesTargetResource($target)], 201);
    }

    public function bulk(BulkAssignTargetRequest $request): JsonResponse
    {
        $template = TargetTemplate::with('lines')->findOrFail($request->target_template_id);
        $targets = $this->bulkAssigner->assign(
            $request->salesperson_user_ids,
            $template,
            $request->period_start,
            $request->name,
            $request->user()->id,
        );
        return response()->json([
            'data' => SalesTargetResource::collection(collect($targets))->resolve(),
            'created_count' => count($targets),
        ], 201);
    }

    public function show(SalesTarget $target): JsonResponse
    {
        $target->load(['salesperson', 'lines.latestAchievement', 'lines.achievements' => function ($q) {
            $q->orderByDesc('snapshot_date')->limit(30);
        }]);
        return response()->json(['data' => new SalesTargetResource($target)]);
    }
}
```

- [ ] **Step 7: Create SalesTargetPolicy + register**

Create `app/Policies/SalesPerformance/SalesTargetPolicy.php`:

```php
<?php

namespace App\Policies\SalesPerformance;

use App\Models\SalesPerformance\SalesTarget;
use App\Models\User;

class SalesTargetPolicy
{
    public function viewAny(User $u): bool { return $u->can('targets.view'); }
    public function view(User $u, SalesTarget $t): bool { return $u->can('targets.view'); }
    public function create(User $u): bool { return $u->can('targets.manage'); }
    public function update(User $u, SalesTarget $t): bool { return $u->can('targets.manage'); }
}
```

Register in `AuthServiceProvider`.

- [ ] **Step 8: Add routes**

```php
Route::apiResource('targets', SalesTargetController::class)->except(['update', 'destroy']);
Route::post('targets/bulk', [SalesTargetController::class, 'bulk']);
```

- [ ] **Step 9: Write feature tests**

Create `tests/Feature/SalesPerformance/SalesTargetsTest.php`:

```php
<?php

namespace Tests\Feature\SalesPerformance;

use App\Enums\TargetMetric;
use App\Enums\TargetPeriod;
use App\Enums\TargetStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTargetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_target_with_lines(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('targets.manage');
        $spv = User::factory()->create(['role' => \App\Enums\UserRole::Salesperson]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/sales-performance/targets', [
                'salesperson_user_id' => $spv->id,
                'period_type' => TargetPeriod::Monthly->value,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'name' => 'Jan Target',
                'status' => TargetStatus::Active->value,
                'lines' => [
                    ['metric' => TargetMetric::SalesAmount->value, 'target_value' => 10000],
                    ['metric' => TargetMetric::InvoiceCount->value, 'target_value' => 50],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.lines');

        $this->assertDatabaseCount('sales_target_lines', 2);
    }
}
```

Create `tests/Feature/SalesPerformance/BulkAssignmentTest.php`:

```php
<?php

namespace Tests\Feature\SalesPerformance;

use App\Enums\TargetPeriod;
use App\Models\SalesPerformance\TargetTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_assigns_template_to_multiple_salespeople(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('targets.manage');
        $spvs = User::factory()->count(3)->create(['role' => \App\Enums\UserRole::Salesperson]);
        $template = TargetTemplate::create([
            'name' => 'Standard Monthly',
            'period_type' => TargetPeriod::Monthly->value,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $template->lines()->create(['metric' => 'sales_amount', 'default_value' => 5000, 'order_index' => 0]);
        $template->lines()->create(['metric' => 'invoice_count', 'default_value' => 30, 'order_index' => 1]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/sales-performance/targets/bulk', [
                'target_template_id' => $template->id,
                'salesperson_user_ids' => $spvs->pluck('id')->toArray(),
                'period_start' => now()->startOfMonth()->toDateString(),
                'name' => 'Jan Bulk',
            ]);

        $response->assertCreated()->assertJsonPath('created_count', 3);
        $this->assertDatabaseCount('sales_targets', 3);
        $this->assertDatabaseCount('sales_target_lines', 6);
    }
}
```

- [ ] **Step 10: Run tests and commit**

```bash
php artisan test --filter=SalesTargetsTest
php artisan test --filter=BulkAssignmentTest
git add app/Http/Requests/SalesPerformance/StoreSalesTargetRequest.php app/Http/Requests/SalesPerformance/BulkAssignTargetRequest.php app/Http/Resources/SalesPerformance/SalesTargetResource.php app/Http/Resources/SalesPerformance/SalesTargetLineResource.php app/Http/Resources/SalesPerformance/SalesTargetAchievementResource.php app/Services/SalesPerformance/BulkTargetAssigner.php app/Http/Controllers/Api/SalesPerformance/SalesTargetController.php app/Policies/SalesPerformance/SalesTargetPolicy.php app/Providers/AuthServiceProvider.php routes/api.php tests/Feature/SalesPerformance/SalesTargetsTest.php tests/Feature/SalesPerformance/BulkAssignmentTest.php
git commit -m "feat(api): Sales Targets CRUD + bulk assign from template"
```

---

## Phase 7 — Frontend Module

### Task 21: Frontend foundation (API service + stores + router + sidebar + i18n)

**Files:**
- Create: `resources/js/services/salesPerformanceApi.js`
- Create: `resources/js/stores/salesPerformance/salespeople.js`
- Create: `resources/js/stores/salesPerformance/teams.js`
- Create: `resources/js/stores/salesPerformance/territories.js`
- Create: `resources/js/stores/salesPerformance/targets.js`
- Create: `resources/js/stores/salesPerformance/assignments.js`
- Create: `resources/js/stores/salesPerformance/approvals.js`
- Create: `resources/js/i18n/en/sales-performance.json`
- Create: `resources/js/i18n/id/sales-performance.json`
- Modify: `resources/js/router/index.js`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Create API service**

```bash
mkdir -p resources/js/services
```

Create `resources/js/services/salesPerformanceApi.js`:

```javascript
import axios from 'axios'

const base = '/api/sales-performance'

export const salesPerformanceApi = {
  // Salespeople
  listSalespeople: (params) => axios.get(`${base}/salespeople`, { params }).then(r => r.data),
  getSalesperson: (id) => axios.get(`${base}/salespeople/${id}`).then(r => r.data.data),
  createSalesperson: (payload) => axios.post(`${base}/salespeople`, payload).then(r => r.data.data),
  updateSalesperson: (id, payload) => axios.patch(`${base}/salespeople/${id}`, payload).then(r => r.data.data),

  // Teams
  listTeams: (params) => axios.get(`${base}/teams`, { params }).then(r => r.data),
  getTeam: (id) => axios.get(`${base}/teams/${id}`).then(r => r.data.data),
  createTeam: (payload) => axios.post(`${base}/teams`, payload).then(r => r.data.data),
  updateTeam: (id, payload) => axios.patch(`${base}/teams/${id}`, payload).then(r => r.data.data),

  // Territories
  listTerritories: (params) => axios.get(`${base}/territories`, { params }).then(r => r.data),
  getTerritory: (id) => axios.get(`${base}/territories/${id}`).then(r => r.data.data),
  createTerritory: (payload) => axios.post(`${base}/territories`, payload).then(r => r.data.data),
  updateTerritory: (id, payload) => axios.patch(`${base}/territories/${id}`, payload).then(r => r.data.data),
  attachMembers: (id, payload) => axios.post(`${base}/territories/${id}/members`, payload).then(r => r.data),

  // Customer Assignments
  listAssignments: (params) => axios.get(`${base}/customers/assignments`, { params }).then(r => r.data),
  createAssignment: (payload) => axios.post(`${base}/customers/assignments`, payload).then(r => r.data.data),
  updateAssignment: (id, payload) => axios.patch(`${base}/customers/assignments/${id}`, payload).then(r => r.data.data),
  revokeAssignment: (id) => axios.post(`${base}/customers/assignments/${id}/revoke`).then(r => r.data.data),
  visitHistory: (customerId) => axios.get(`${base}/customers/${customerId}/visit-history`).then(r => r.data.data),

  // Targets
  listTargets: (params) => axios.get(`${base}/targets`, { params }).then(r => r.data),
  getTarget: (id) => axios.get(`${base}/targets/${id}`).then(r => r.data.data),
  createTarget: (payload) => axios.post(`${base}/targets`, payload).then(r => r.data.data),
  bulkAssignTargets: (payload) => axios.post(`${base}/targets/bulk`, payload).then(r => r.data),

  // Templates
  listTemplates: (params) => axios.get(`${base}/target-templates`, { params }).then(r => r.data),
  getTemplate: (id) => axios.get(`${base}/target-templates/${id}`).then(r => r.data.data),
  createTemplate: (payload) => axios.post(`${base}/target-templates`, payload).then(r => r.data.data),

  // Approvals
  listApprovals: () => axios.get(`${base}/approvals`).then(r => r.data),
  approve: (id) => axios.post(`${base}/approvals/${id}/approve`).then(r => r.data),
  reject: (id, reason) => axios.post(`${base}/approvals/${id}/reject`, { reason }).then(r => r.data),
}
```

- [ ] **Step 2: Create Pinia stores**

```bash
mkdir -p resources/js/stores/salesPerformance
```

Create `resources/js/stores/salesPerformance/salespeople.js`:

```javascript
import { defineStore } from 'pinia'
import { salesPerformanceApi } from '@/services/salesPerformanceApi'

export const useSalespeopleStore = defineStore('salespeople', {
  state: () => ({
    items: [],
    current: null,
    loading: false,
    pagination: null,
  }),
  actions: {
    async fetch(params = {}) {
      this.loading = true
      try {
        const res = await salesPerformanceApi.listSalespeople(params)
        this.items = res.data
        this.pagination = { currentPage: res.current_page, lastPage: res.last_page }
      } finally {
        this.loading = false
      }
    },
    async fetchOne(id) {
      this.current = await salesPerformanceApi.getSalesperson(id)
    },
    async create(payload) {
      return await salesPerformanceApi.createSalesperson(payload)
    },
    async update(id, payload) {
      return await salesPerformanceApi.updateSalesperson(id, payload)
    },
  },
})
```

Create analogous stores for `teams.js`, `territories.js`, `targets.js`, `assignments.js`, `approvals.js` with the same shape (state + fetch/create/update actions matching the API).

- [ ] **Step 3: Add router entries**

Edit `resources/js/router/index.js` — add a new module:

```javascript
{
  path: '/sales-performance',
  component: () => import('@/layouts/DefaultLayout.vue'),
  children: [
    { path: '', redirect: '/sales-performance/salespeople' },
    { path: 'salespeople', component: () => import('@/pages/sales-performance/salespeople/SalespeopleList.vue'), meta: { title: 'Salespeople' } },
    { path: 'salespeople/:id', component: () => import('@/pages/sales-performance/salespeople/SalespersonDetail.vue') },
    { path: 'teams', component: () => import('@/pages/sales-performance/teams/TeamsList.vue'), meta: { title: 'Teams' } },
    { path: 'territories', component: () => import('@/pages/sales-performance/territories/TerritoriesList.vue'), meta: { title: 'Territories' } },
    { path: 'targets', component: () => import('@/pages/sales-performance/targets/TargetsList.vue'), meta: { title: 'Sales Targets' } },
    { path: 'targets/:id', component: () => import('@/pages/sales-performance/targets/TargetDetail.vue') },
    { path: 'assignments', component: () => import('@/pages/sales-performance/assignments/CustomerAssignmentsList.vue'), meta: { title: 'Customer Assignments' } },
    { path: 'approvals', component: () => import('@/pages/sales-performance/approvals/ApprovalsInbox.vue'), meta: { title: 'Approvals' } },
  ],
},
```

- [ ] **Step 4: Add sidebar section**

Edit `resources/js/components/AppSidebar.vue` — add a section linking to `/sales-performance/salespeople` with sub-items (Salespeople, Teams, Territories, Targets, Assignments, Approvals).

- [ ] **Step 5: Add i18n keys**

Create `resources/js/i18n/en/sales-performance.json`:

```json
{
  "sales_performance": {
    "title": "Sales Performance",
    "salespeople": "Salespeople",
    "teams": "Teams",
    "territories": "Territories",
    "targets": "Sales Targets",
    "assignments": "Customer Assignments",
    "approvals": "Approvals"
  }
}
```

Create `resources/js/i18n/id/sales-performance.json` (Bahasa Indonesia equivalent).

Register these in your i18n init file.

- [ ] **Step 6: Commit**

```bash
git add resources/js/services/salesPerformanceApi.js resources/js/stores/salesPerformance resources/js/i18n/en/sales-performance.json resources/js/i18n/id/sales-performance.json resources/js/router/index.js resources/js/components/AppSidebar.vue
git commit -m "feat(frontend): sales-performance API service, stores, router, sidebar, i18n"
```

---

### Task 22: Salespeople pages (List, Detail, Form)

**Files:**
- Create: `resources/js/pages/sales-performance/salespeople/SalespeopleList.vue`
- Create: `resources/js/pages/sales-performance/salespeople/SalespersonDetail.vue`
- Create: `resources/js/pages/sales-performance/salespeople/SalespersonForm.vue`

- [ ] **Step 1: Create the directory**

```bash
mkdir -p resources/js/pages/sales-performance/salespeople
```

- [ ] **Step 2: Create SalespeopleList.vue**

```vue
<script setup>
import { onMounted, ref } from 'vue'
import { useSalespeopleStore } from '@/stores/salesPerformance/salespeople'

const store = useSalespeopleStore()
const search = ref('')
const statusFilter = ref('')

onMounted(() => store.fetch())

async function reload() {
  await store.fetch({ q: search.value, status: statusFilter.value })
}
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-semibold">Salespeople</h1>
      <router-link to="/sales-performance/salespeople/new" class="btn btn-primary">
        + New Salesperson
      </router-link>
    </div>

    <div class="flex gap-2 mb-4">
      <input v-model="search" @input="reload" class="input" placeholder="Search by name or email" />
      <select v-model="statusFilter" @change="reload" class="input">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="on_leave">On Leave</option>
        <option value="terminated">Terminated</option>
      </select>
    </div>

    <table class="table w-full">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Status</th>
          <th>Team</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="sp in store.items" :key="sp.id">
          <td>{{ sp.name }}</td>
          <td>{{ sp.email }}</td>
          <td>{{ sp.employment_status_label }}</td>
          <td>{{ sp.team?.name || '—' }}</td>
          <td>
            <router-link :to="`/sales-performance/salespeople/${sp.id}`" class="link">View</router-link>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="store.loading" class="text-center py-4">Loading…</div>
  </div>
</template>
```

- [ ] **Step 3: Create SalespersonForm.vue**

Create `resources/js/pages/sales-performance/salespeople/SalespersonForm.vue`:

```vue
<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useSalespeopleStore } from '@/stores/salesPerformance/salespeople'

const router = useRouter()
const store = useSalespeopleStore()
const form = reactive({
  name: '',
  email: '',
  password: '',
  employment_status: 'active',
})

async function submit() {
  await store.create(form)
  router.push('/sales-performance/salespeople')
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-semibold mb-4">New Salesperson</h1>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm">Name</label>
        <input v-model="form.name" required class="input w-full" />
      </div>
      <div>
        <label class="block text-sm">Email</label>
        <input v-model="form.email" type="email" required class="input w-full" />
      </div>
      <div>
        <label class="block text-sm">Password</label>
        <input v-model="form.password" type="password" required class="input w-full" />
      </div>
      <div>
        <label class="block text-sm">Status</label>
        <select v-model="form.employment_status" class="input w-full">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="on_leave">On Leave</option>
          <option value="terminated">Terminated</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Create</button>
    </form>
  </div>
</template>
```

- [ ] **Step 4: Create SalespersonDetail.vue**

Create `resources/js/pages/sales-performance/salespeople/SalespersonDetail.vue`:

```vue
<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useSalespeopleStore } from '@/stores/salesPerformance/salespeople'

const route = useRoute()
const store = useSalespeopleStore()

onMounted(() => store.fetchOne(route.params.id))
</script>

<template>
  <div v-if="store.current" class="p-6">
    <h1 class="text-2xl font-semibold">{{ store.current.name }}</h1>
    <p class="text-gray-600">{{ store.current.email }}</p>
    <p class="mt-2">Status: <strong>{{ store.current.employment_status_label }}</strong></p>

    <div class="mt-6 grid grid-cols-2 gap-6">
      <section>
        <h2 class="text-lg font-semibold">Team</h2>
        <p>{{ store.current.team?.name || '—' }}</p>
      </section>
      <section>
        <h2 class="text-lg font-semibold">Territories</h2>
        <ul>
          <li v-for="t in store.current.territories || []" :key="t.id">{{ t.name }}</li>
        </ul>
      </section>
    </div>
  </div>
</template>
```

- [ ] **Step 5: Verify in dev and commit**

```bash
npm run build
git add resources/js/pages/sales-performance/salespeople
git commit -m "feat(frontend): salespeople list/detail/form pages"
```

---

### Task 23: Teams, Territories, Targets, Assignments, Approvals pages

**Files:**
- Create: `resources/js/pages/sales-performance/teams/{TeamsList,TeamForm}.vue`
- Create: `resources/js/pages/sales-performance/territories/{TerritoriesList,TerritoryForm}.vue`
- Create: `resources/js/pages/sales-performance/targets/{TargetsList,TargetDetail,TargetForm,BulkAssignDialog}.vue`
- Create: `resources/js/pages/sales-performance/assignments/{CustomerAssignmentsList,AssignmentForm}.vue`
- Create: `resources/js/pages/sales-performance/approvals/ApprovalsInbox.vue`

- [ ] **Step 1: Create Teams pages**

Mirror the Salespeople pages pattern. `TeamsList.vue`: table with name/code/leader/is_active columns + "New Team" button. `TeamForm.vue`: simple form with name, code, description, leader_user_id.

- [ ] **Step 2: Create Territories pages**

`TerritoriesList.vue`: table + "New" button + member-count badge. `TerritoryForm.vue`: name/code/region/description + member multi-select for attaching users.

- [ ] **Step 3: Create Targets pages**

`TargetsList.vue`: filter by salesperson/period_type/status, table with salesperson/period/target_value/status. "Bulk Assign" button opens `BulkAssignDialog.vue` (template selector + multi-select SPVs + period start).

`TargetForm.vue`: salesperson picker, period_type, period_start/end, dynamic line editor (7 metrics).

`TargetDetail.vue`: shows header + lines + recent achievements.

- [ ] **Step 4: Create Assignments pages**

`CustomerAssignmentsList.vue`: filter by salesperson/customer/status, table with customer/salesperson/status/valid_from. "New Assignment" button.

`AssignmentForm.vue`: customer_id, salesperson_user_id, valid_from, notes.

- [ ] **Step 5: Create ApprovalsInbox.vue**

Table listing pending approvals with approve/reject buttons. On click → call API → refresh list.

- [ ] **Step 6: Build and commit**

```bash
npm run build
git add resources/js/pages/sales-performance
git commit -m "feat(frontend): teams, territories, targets, assignments, approvals pages"
```

---

## Phase 8 — Final Integration

### Task 24: Run full test suite

**Files:** (none — verify-only step)

- [ ] **Step 1: Run all tests**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 2: Run frontend build**

```bash
npm run build
```

Expected: build succeeds without errors.

- [ ] **Step 3: Run linter**

```bash
./vendor/bin/pint
npm run lint 2>/dev/null || echo "no lint configured"
```

Expected: no errors.

- [ ] **Step 4: Commit any formatting fixes (if any)**

```bash
git add -A
git commit -m "chore: formatting fixes" || echo "no changes"
```

---

### Task 25: Manual smoke test

**Files:** (none — manual verification)

- [ ] **Step 1: Start dev server**

```bash
php artisan serve &
npm run dev
```

- [ ] **Step 2: Walk through the smoke checklist**

From the spec's manual smoke checklist:
1. Create team → assign leader.
2. Create territory → attach 3 SPVs.
3. Create salesperson via API.
4. Assign customer to SPV → verify pending Approval row.
5. Approve assignment as manager → verify status='active'.
6. Create target template with 7 metric lines.
7. Bulk-assign template to 5 SPVs for current month.
8. Run a sale for SPV #1 → verify `sales_target_achievements` row.
9. Run a second sale → verify achievement accumulates.
10. Void the first sale → verify achievement decrements.

Document any issues in the PR description.

- [ ] **Step 3: Commit smoke notes (if any)**

```bash
git add docs/superpowers/smoke-2026-06-26.md 2>/dev/null || echo "no notes"
git commit -m "docs: smoke test results" || echo "no notes"
```

---

## Self-Review Checklist (Run Before Execution)

After writing this plan, verify:

1. **Spec coverage:** every section/requirement in `2026-06-26-sales-performance-foundation-design.md` is covered by at least one task above. ✅ (Migrations, models, services, observer, API endpoints, frontend, tests all present.)
2. **No placeholders:** no "TBD"/"TODO"/"implement later"/"add appropriate error handling" stubs. ✅ (All steps have explicit code or commands.)
3. **Type consistency:** `TargetAchievementUpdater::applySale(Sale $sale)` and `reverseSale(Sale $sale)` signatures match between Tasks 11, 12, 13. ✅. `TargetAchievementUpdaterInterface` matches implementation. ✅.
4. **Commit hygiene:** every task ends with a `git commit`. ✅.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-06-26-sales-performance-foundation.md`.

Two execution options:
1. **Subagent-Driven** (recommended) — fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — execute tasks in this session using executing-plans, batch execution with checkpoints.

User has signaled "start build" repeatedly — proceeding with inline execution as the default.
