<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salesperson_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'expired', 'revoked'])->default('pending');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approval_id')->nullable()->constrained('approvals')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['salesperson_user_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('valid_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_assignments');
    }
};