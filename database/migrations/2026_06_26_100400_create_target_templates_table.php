<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('target_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'annual']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_templates');
    }
};