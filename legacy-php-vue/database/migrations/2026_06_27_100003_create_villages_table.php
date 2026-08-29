<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 25);
            $table->foreignId('commune_id')->constrained('communes')->cascadeOnDelete();
            $table->string('name_en', 100);
            $table->string('name_km', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['commune_id', 'code']);
            $table->index('name_en');
            $table->index('name_km');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
