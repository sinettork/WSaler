<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('method', 30); // cash|credit|bank_transfer|e_wallet|card
            $table->decimal('amount', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->dateTime('paid_at');
            $table->timestamps();

            $table->index('sale_id');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
