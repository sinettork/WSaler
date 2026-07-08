# Test Migration Issues Report

## Problem Summary
The test suite is failing because the performance indexes migration (`2026_07_08_010232_add_performance_indexes_to_critical_tables.php`) is trying to create indexes that already exist in SQLite (test database).

## Root Cause
1. Tests use SQLite in-memory database (`:memory:`)
2. Migrations run for each test, but some base migrations already create these indexes
3. SQLite throws "index already exists" error when trying to create duplicate indexes
4. The `indexExists()` check doesn't work properly for SQLite because:
   - Doctrine schema manager method `getDoctrineSchemaManager()` is not available for SQLite in Laravel 11
   - The fallback logic was incomplete

## Failed Tests
All 22 unit tests and 150 feature tests failing with:
```
SQLSTATE[HY000]: General error: 1 index sale_payments_method_index already exists
```

## Recommended Solutions

### Option 1: Skip Performance Index Migration in Tests (RECOMMENDED)
Add environment check at the top of the migration:
```php
public function up(): void
{
    // Skip in test environment - indexes may already exist from base migrations
    if (app()->environment('testing')) {
        return;
    }
    
    // ... rest of migration code
}
```

**Pros:**
- Simple and clean
- Tests run faster (skip unnecessary index creation)
- No risk of duplicate index errors
- Indexes still applied in production

**Cons:**
- Won't test index creation in test suite (acceptable tradeoff)

### Option 2: Wrap All Index Creation in Try-Catch for SQLite
Wrap each index creation for SQLite in try-catch blocks.

**Pros:**
- Tests actually create indexes when possible
- More thorough testing

**Cons:**
- More verbose code
- Slower test execution
- Silent failures could hide real issues

### Option 3: Use Raw SQL with IF NOT EXISTS
Use database-specific raw SQL for index creation.

**Pros:**
- Most database-agnostic
- Proper deduplication

**Cons:**
- More complex code
- Requires different SQL for each database driver
- Loses Laravel's migration abstraction

## Immediate Action Required
1. Apply Option 1 (environment check) to unblock tests
2. Document that performance indexes are not tested
3. Verify indexes are created properly in production MySQL database

## Files Affected
- `database/migrations/2026_07_08_010232_add_performance_indexes_to_critical_tables.php`
- `database/migrations/2026_07_07_120000_fix_role_assignments.php` (already fixed)

## Test Status
- ✅ 2 passing (ExampleTest)
- ❌ 152 failing (all due to migration issue)
- 📊 Total: 154 tests

Once migration is fixed, we expect tests to reveal actual application logic issues that need attention.
