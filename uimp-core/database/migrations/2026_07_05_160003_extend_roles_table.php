<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend Spatie's roles table with SDD §4.2 fields:
 * - description_en, description_ar (bilingual descriptions)
 * - is_system (bool, marks system-defined roles that shouldn't be deleted)
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('permission.table_names.roles', 'roles');

        Schema::table($tableName, function (Blueprint $table) {
            $table->string('description_en')->nullable()->after('guard_name');
            $table->string('description_ar')->nullable()->after('description_en');
            $table->boolean('is_system')->default(false)->after('description_ar');
        });
    }

    public function down(): void
    {
        $tableName = config('permission.table_names.roles', 'roles');

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn(['description_en', 'description_ar', 'is_system']);
        });
    }
};
