<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('reference_number', 50)->unique();
            $table->string('status', 20)->default('draft'); // draft, pending, approved, paid, cancelled
            $table->date('expense_date');
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->string('payment_method', 30)->nullable(); // cash, bank_transfer, card, check, mobile_money, other
            $table->date('payment_date')->nullable();
            $table->string('reference')->nullable(); // receipt number, invoice number, etc.
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approvable_id')->nullable();
            $table->string('approvable_type')->nullable();
            $table->index(['approvable_type', 'approvable_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};