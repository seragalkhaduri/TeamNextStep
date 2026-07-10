<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Employees tables (SDD §4.2):
 * - employees: single-table with staff_type discriminator (§3.2.2, §7.2 trade-off 4)
 * - employee_departments: M:M pivot
 * - employee_history: append-only change log (FR-EMP-004)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── Employees ───────────────────────────────────────────
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('institutional_id')->comment('Unique staff ID');
            $table->string('staff_type');         // StaffType enum: ACADEMIC / NON_ACADEMIC
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('academic_rank')->nullable()->comment('Only for ACADEMIC staff');
            $table->date('hire_date');
            $table->string('status')->default('ACTIVE');
            $table->uuid('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Indexes per SDD §4.3
        if (config('database.default') === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX employees_institutional_id_unique_active
                 ON employees (institutional_id) WHERE deleted_at IS NULL'
            );
            DB::statement(
                "CREATE INDEX employees_name_en_fts ON employees
                 USING GIN (to_tsvector('english', name_en))"
            );
        }

        // ─── Employee-Departments pivot (M:M) ────────────────────
        Schema::create('employee_departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUuid('department_id')->constrained('departments')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['employee_id', 'department_id']);
        });

        // ─── Employee History (append-only, FR-EMP-004) ──────────
        Schema::create('employee_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('field_changed');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            // No updated_at — append-only
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_history');
        Schema::dropIfExists('employee_departments');
        Schema::dropIfExists('employees');
    }
};
