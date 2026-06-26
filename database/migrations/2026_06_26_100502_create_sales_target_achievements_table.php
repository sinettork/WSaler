<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_target_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_target_line_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('achieved_value', 18, 4)->default(0);
            $table->decimal('achievement_pct', 8, 4)->default(0);
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();
            $table->unique(['sales_target_line_id', 'snapshot_date'], 'achievement_line_date_unique');
            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_target_achievements');
    }
};