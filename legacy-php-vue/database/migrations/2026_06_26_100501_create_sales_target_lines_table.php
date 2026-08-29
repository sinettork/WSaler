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