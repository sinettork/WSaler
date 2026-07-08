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
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('module')->nullable(); // e.g., 'products', 'sales', 'inventory'
            $table->string('resource_type')->nullable(); // model class name
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('before')->nullable(); // JSON snapshot before change
            $table->json('after')->nullable(); // JSON snapshot after change
            $table->string('event')->nullable(); // created, updated, deleted, approved, rejected
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn([
                'module',
                'resource_type',
                'resource_id',
                'before',
                'after',
                'event',
            ]);
        });
    }
};
