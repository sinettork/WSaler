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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approvable_type'); // e.g., App\Models\PurchaseOrder
            $table->unsignedBigInteger('approvable_id');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->string('required_level')->nullable(); // manager, admin, etc.
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // amount, risk_level, etc.
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['status', 'approver_id']);
            $table->index(['requested_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
