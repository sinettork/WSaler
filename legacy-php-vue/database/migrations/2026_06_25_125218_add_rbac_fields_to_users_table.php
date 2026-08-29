<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->json('warehouse_ids')->nullable();
            $table->json('customer_ids')->nullable();
            $table->string('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->datetime('password_changed_at')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->datetime('locked_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn([
                'warehouse_ids',
                'customer_ids',
                'two_factor_secret',
                'two_factor_enabled',
                'password_changed_at',
                'login_attempts',
                'locked_until',
            ]);
        });
    }
};
