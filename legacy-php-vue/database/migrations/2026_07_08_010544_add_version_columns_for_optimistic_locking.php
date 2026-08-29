<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add version columns to critical tables for optimistic locking.
     * 
     * Optimistic locking prevents lost updates when multiple users edit
     * the same record concurrently. The version column increments on each
     * update and is checked before saving to detect conflicts.
     */
    public function up(): void
    {
        // Batches - critical for stock management, prevent race conditions
        Schema::table('batches', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        // Sales - prevent concurrent modifications of completed sales
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        // Customers - protect credit limit and balance updates
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        // Products - prevent pricing and configuration conflicts
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });

        // Stock movements - ensure audit trail integrity
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('updated_at');
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });
    }
};
