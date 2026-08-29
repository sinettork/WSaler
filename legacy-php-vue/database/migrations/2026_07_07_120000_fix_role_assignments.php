<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Fix the role-link bug:
     *
     * Symptom:  Admin user (users.role = 'admin') gets 403 Forbidden on every
     *           permission-protected endpoint (e.g. /api/customers).
     *
     * Cause:    Two parallel role systems — `users.role` enum column and the
     *           `model_has_roles` polymorphic link. HasPermissions::hasPermissionTo()
     *           walks the latter, but neither RoleSeeder nor AuthController::register()
     *           was populating it. Result: every user has zero permissions at runtime.
     *
     * Fix:      Idempotently (1) run RolePermissionSeeder to make sure role rows
     *           + role↔permission links exist with the corrected names from the
     *           prior fix (administrator → admin, warehouse_staff → warehouse, etc.);
     *           then (2) for every user, sync them to the Role whose name matches
     *           their `users.role` enum value.
     *
     * Safe to run multiple times: `firstOrCreate` in the seeder is idempotent,
     * and syncRoles() replaces the user's role set wholesale.
     */
    public function up(): void
    {
        // 1. Drop any legacy role rows that no longer match the enum values.
        //    These are orphans from the original buggy seeder. We must drop
        //    their model_has_roles rows first to avoid FK violations.
        $legacyNames = [
            'administrator',
            'warehouse_staff',
            'purchasing_staff',
            'delivery_staff',
            'sales_staff',
        ];
        $legacyIds = DB::table('roles')->whereIn('name', $legacyNames)->pluck('id');
        if ($legacyIds->isNotEmpty()) {
            DB::table('model_has_roles')->whereIn('role_id', $legacyIds)->delete();
            DB::table('roles')->whereIn('name', $legacyNames)->delete();
        }

        // 2. Run the seeder so all roles + permissions exist with the
        //    corrected names. Seeder is idempotent via firstOrCreate.
        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);

        // 3. Link every user to the role matching their users.role enum value.
        //    This is the actual bug fix — populating model_has_roles so
        //    hasPermissionTo() can find the user's permissions.
        $linked = 0;
        $unmatched = 0;
        foreach (User::all() as $user) {
            $roleValue = $user->role instanceof UserRole ? $user->role->value : $user->role;
            $role = Role::where('name', $roleValue)->first();
            if (! $role) {
                $unmatched++;
                continue;
            }
            $user->syncRoles([$role]);
            $linked++;
        }

        // Surface the outcome in the migration log so it's obvious whether
        // the fix applied cleanly (e.g. "Linked 6 users; 0 unmatched.").
        $total = User::count();
        // Note: Only output when running in console context (not during tests)
        if (method_exists($this, 'command') && $this->command) {
            $this->command->info("  → Role assignment fix: linked {$linked} / {$total} users" . ($unmatched ? " ({$unmatched} unmatched)" : ''));
        }
    }

    /**
     * No-op rollback: we don't drop the role rows or user-role links on
     * rollback, because doing so would re-introduce the original bug. If
     * you really need to undo, run `php artisan migrate:fresh --seed`.
     */
    public function down(): void
    {
        // intentionally empty — see comment above
    }
};
