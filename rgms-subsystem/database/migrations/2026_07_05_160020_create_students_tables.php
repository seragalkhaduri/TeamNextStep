<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Students table + student_programs pivot (SDD §4.2).
 *
 * Key constraints:
 * - Composite unique on (national_id, institutional_id) for dedup (FR-STU-002)
 * - GIN full-text indexes on name_en (english) and name_ar (arabic) (SDD §4.3)
 * - Partial unique index on institutional_id where not deleted
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('institutional_id')->comment('e.g. STU-2024-001234');
            $table->string('national_id');
            $table->string('name_en');
            $table->string('name_ar');
            $table->date('date_of_birth');
            $table->string('gender');           // Gender enum
            $table->string('nationality')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('enrollment_status')->default('ACTIVE'); // EnrollmentStatus enum
            $table->date('admission_date');
            $table->date('graduation_date')->nullable();
            $table->uuid('user_id')->nullable()->comment('FK for self-service login');
            $table->uuid('created_by')->comment('FK users — who created this record');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });

        // ─── DB-level constraints and indexes (SDD §4.3) ─────────

        if (config('database.default') === 'pgsql') {
            // Composite unique constraint for dedup (FR-STU-002)
            // Enforced at DB level, not just app validation
            DB::statement(
                'ALTER TABLE students ADD CONSTRAINT students_national_institutional_unique
                 UNIQUE (national_id, institutional_id)'
            );

            // Partial unique index on institutional_id where not soft-deleted
            DB::statement(
                'CREATE UNIQUE INDEX students_institutional_id_unique_active
                 ON students (institutional_id) WHERE deleted_at IS NULL'
            );

            // Plain index on national_id
            DB::statement('CREATE INDEX students_national_id_idx ON students (national_id)');

            // GIN full-text indexes for bilingual search
            DB::statement(
                "CREATE INDEX students_name_en_fts ON students
                 USING GIN (to_tsvector('english', name_en))"
            );
            DB::statement(
                "CREATE INDEX students_name_ar_fts ON students
                 USING GIN (to_tsvector('arabic', name_ar))"
            );

            // Index on enrollment_status where not deleted
            DB::statement(
                'CREATE INDEX students_enrollment_status_active
                 ON students (enrollment_status) WHERE deleted_at IS NULL'
            );
        }

        // ─── Student-Programs pivot (M:M) ────────────────────────
        Schema::create('student_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('programs')->cascadeOnDelete();
            $table->date('enrollment_date');

            $table->unique(['student_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_programs');
        Schema::dropIfExists('students');
    }
};
