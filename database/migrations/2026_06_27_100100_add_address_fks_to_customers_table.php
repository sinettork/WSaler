<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('address')->constrained('provinces')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('province_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->after('district_id')->constrained('communes')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('commune_id')->constrained('villages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['commune_id']);
            $table->dropForeign(['village_id']);
            $table->dropColumn(['province_id', 'district_id', 'commune_id', 'village_id']);
        });
    }
};
