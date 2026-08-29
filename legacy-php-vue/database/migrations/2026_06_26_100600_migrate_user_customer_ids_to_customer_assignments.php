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