<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing indexes for performance optimization on frequently queried columns.
     * These indexes improve query performance for filtering, sorting, and joins.
     */
    public function up(): void
    {
        // Sale items - frequently queried by unit and product for reporting
        Schema::table('sale_items', function (Blueprint $table) {
            if (!$this->indexExists('sale_items', 'sale_items_unit_id_index')) {
                $table->index('unit_id');
            }
        });

        // Sale payments - frequently filtered by payment method
        Schema::table('sale_payments', function (Blueprint $table) {
            if (!$this->indexExists('sale_payments', 'sale_payments_method_index')) {
                $table->index('method');
            }
            if (!$this->indexExists('sale_payments', 'sale_payments_paid_at_index')) {
                $table->index('paid_at');
            }
        });

        // Batches - critical for FEFO queries and stock lookups
        Schema::table('batches', function (Blueprint $table) {
            if (!$this->indexExists('batches', 'batches_status_expiry_date_index')) {
                // Composite index for status + expiry_date for FEFO queries
                $table->index(['status', 'expiry_date']);
            }
            if (!$this->indexExists('batches', 'batches_expiry_date_index')) {
                $table->index('expiry_date');
            }
        });

        // Products - frequently searched and filtered
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_sku_index')) {
                $table->index('sku');
            }
            if (!$this->indexExists('products', 'products_barcode_index')) {
                $table->index('barcode');
            }
            if (!$this->indexExists('products', 'products_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('products', 'products_track_stock_index')) {
                $table->index('track_stock');
            }
        });

        // Customers - searched by name, phone, credit status
        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'customers_name_index')) {
                $table->index('name');
            }
            if (!$this->indexExists('customers', 'customers_phone_index')) {
                $table->index('phone');
            }
            if (!$this->indexExists('customers', 'customers_credit_status_index')) {
                $table->index('credit_status');
            }
        });

        // Suppliers - searched by name
        Schema::table('suppliers', function (Blueprint $table) {
            if (!$this->indexExists('suppliers', 'suppliers_name_index')) {
                $table->index('name');
            }
        });

        // Sales - critical for reporting and filtering
        Schema::table('sales', function (Blueprint $table) {
            if (!$this->indexExists('sales', 'sales_invoice_number_index')) {
                $table->index('invoice_number');
            }
            // Composite index for date range queries by warehouse
            if (!$this->indexExists('sales', 'sales_warehouse_sold_at_index')) {
                $table->index(['warehouse_id', 'sold_at']);
            }
            // Composite index for customer sales history
            if (!$this->indexExists('sales', 'sales_customer_sold_at_index')) {
                $table->index(['customer_id', 'sold_at']);
            }
        });

        // Stock movements - auditing and history queries
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!$this->indexExists('stock_movements', 'stock_movements_type_index')) {
                $table->index('type');
            }
            if (!$this->indexExists('stock_movements', 'stock_movements_occurred_at_index')) {
                $table->index('occurred_at');
            }
            // Composite for product history queries
            if (!$this->indexExists('stock_movements', 'stock_movements_product_occurred_at_index')) {
                $table->index(['product_id', 'occurred_at']);
            }
        });

        // Product variations - frequently queried with products
        Schema::table('product_variations', function (Blueprint $table) {
            if (!$this->indexExists('product_variations', 'product_variations_sku_index')) {
                $table->index('sku');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex(['unit_id']);
        });

        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropIndex(['method']);
            $table->dropIndex(['paid_at']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['status', 'expiry_date']);
            $table->dropIndex(['expiry_date']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropIndex(['barcode']);
            $table->dropIndex(['status']);
            $table->dropIndex(['track_stock']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['credit_status']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['invoice_number']);
            $table->dropIndex(['warehouse_id', 'sold_at']);
            $table->dropIndex(['customer_id', 'sold_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['occurred_at']);
            $table->dropIndex(['product_id', 'occurred_at']);
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropIndex(['sku']);
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
        $doctrineTable = $doctrineSchemaManager->introspectTable($table);
        
        return $doctrineTable->hasIndex($index);
    }
};
