<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: funding_source_id is created here WITHOUT its foreign key
     * constraint (fk_rg_funding) because funding_sources does not yet
     * exist and funding_sources itself has a FK back to research_groups
     * (circular dependency — SDD §4.1.1). The FK constraint is added in
     * a later migration once funding_sources exists.
     */
    public function up(): void
    {
        Schema::create('research_groups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('group_name', 255);
            $table->string('research_field', 200);
            $table->string('research_area', 200);
            $table->enum('status', ['Draft', 'Active', 'Suspended', 'Archived'])
                ->default('Draft');
            $table->string('pi_staff_id', 100);
            $table->string('department_ref_id', 100)->nullable();

            // FK constraint (fk_rg_funding) added later — see
            // 2026_06_01_000010_add_funding_source_fk_to_research_groups_table.php
            $table->uuid('funding_source_id')->nullable();

            $table->decimal('budget_allocation', 15, 2)->nullable();

            // Standard RGMS audit columns (SDD §4.1.2)
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            // MySQL has no native partial/filtered index support (unlike
            // PostgreSQL). This stored generated column mirrors group_name
            // only while the row is active (deleted_at IS NULL) and is
            // NULL otherwise; MySQL UNIQUE indexes permit multiple NULLs,
            // which reproduces the effect of SDD §4.5.3's partial unique
            // constraint "group_name unique among active records".
            $table->string('group_name_active_key', 255)
                ->nullable()
                ->storedAs('IF(deleted_at IS NULL, group_name, NULL)');
        });

        Schema::table('research_groups', function (Blueprint $table): void {
            $table->unique('group_name_active_key', 'uq_rg_name_active');

            // Full (non-partial) indexes — MySQL cannot filter by
            // deleted_at IS NULL as PostgreSQL does; functionally
            // equivalent for query performance, marginally larger on disk.
            $table->index('status', 'idx_rg_status');
            $table->index('pi_staff_id', 'idx_rg_pi');
            $table->index(['research_field', 'research_area'], 'idx_rg_field_area');
            $table->index('created_at', 'idx_rg_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_groups');
    }
};