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