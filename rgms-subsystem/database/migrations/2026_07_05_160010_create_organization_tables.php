<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organization tables: faculties, departments, programs (SDD §4.2).
 * Departments support self-referential hierarchy (FR-ORG-003).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── Faculties ───────────────────────────────────────────────
        Schema::create('faculties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('code')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Departments ─────────────────────────────────────────────
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('code')->unique();
            $table->foreignUuid('faculty_id')->constrained('faculties');
            // Self-referential FK for hierarchy (FR-ORG-003)
            $table->uuid('parent_department_id')->nullable();
            $table->foreign('parent_department_id')
                  ->references('id')->on('departments')
                  ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Programs ────────────────────────────────────────────────
        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('degree_level'); // e.g. BSc, MSc, PhD
            $table->foreignUuid('department_id')->constrained('departments');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('faculties');
    }
};
