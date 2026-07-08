<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for hot search and filter columns that were missing from
     * the original table creations. These keep the user, sale, and stock
     * listing endpoints fast as data grows.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('name', 'idx_users_name');
            $table->index('role', 'idx_users_role');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            // Composite helps the warehouse-scoped history endpoint that orders by occurred_at.
            $table->index(['warehouse_id', 'occurred_at'], 'idx_stock_movements_warehouse_occurred_at');
        });

        Schema::table('batches', function (Blueprint $table) {
            // Composite helps the POS batch lookup (product + warehouse, ordered by expiry).
            $table->index(['product_id', 'warehouse_id', 'status'], 'idx_batches_product_warehouse_status');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // sale_items is hot on the sales detail and reporting paths.
            $table->index('product_id', 'idx_sale_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name');
            $table->dropIndex('idx_users_role');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_warehouse_occurred_at');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex('idx_batches_product_warehouse_status');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_product_id');
        });
    }
};
