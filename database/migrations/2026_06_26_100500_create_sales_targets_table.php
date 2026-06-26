<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salesperson_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'annual']);
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('target_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('status', ['draft', 'active', 'achieved', 'expired', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['salesperson_user_id', 'period_type', 'period_start'], 'sales_targets_unique_period');
            $table->index(['status', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};