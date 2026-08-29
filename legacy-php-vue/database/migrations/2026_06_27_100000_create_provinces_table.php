<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name_en', 100);
            $table->string('name_km', 100);
            $table->enum('type', ['province', 'municipality'])->default('province');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['type', 'sort_order']);
            $table->index('name_en');
            $table->index('name_km');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
