<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('territory_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->unique(['territory_id', 'user_id', 'valid_from'], 'territory_user_unique');
            $table->index(['user_id', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territory_user');
    }
};