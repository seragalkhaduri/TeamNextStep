<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('group_id');
            $table->foreign('group_id', 'fk_gm_group')
                ->references('id')->on('research_groups')
                ->onDelete('restrict');

            $table->string('member_uimp_id', 100);
            $table->enum('member_type', ['Staff', 'Student', 'External']);
            $table->enum('role', [
                'PI',
                'Co-I',
                'Research-Assistant',
                'Graduate-Researcher',
                'External-Collaborator',
            ]);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('workload_percentage');
            $table->enum('status', ['Active', 'Inactive', 'Suspended'])
                ->default('Active');

            // Standard RGMS audit columns (SDD §4.1.2)
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            $table->index(['group_id', 'status'], 'idx_gm_group_status');
            $table->index(['member_uimp_id', 'status'], 'idx_gm_member_status');
            $table->index('role', 'idx_gm_role');
        });

        // CHECK constraint (SDD §4.5.2) — Laravel's fluent Schema Builder
        // has no cross-driver check() helper; added via raw DDL for
        // explicit, portable enforcement at the database level.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE group_memberships '
                . 'ADD CONSTRAINT chk_gm_workload CHECK (workload_percentage BETWEEN 1 AND 100)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_memberships');
    }
};