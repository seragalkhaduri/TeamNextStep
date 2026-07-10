<?php

declare(strict_types=1);

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
        Schema::create('budget_allocations', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('research_group_id');
            $table->foreign('research_group_id', 'fk_ba_group')
                ->references('id')->on('research_groups')
                ->onDelete('restrict');

            $table->uuid('project_id')->nullable();
            $table->foreign('project_id', 'fk_ba_project')
                ->references('id')->on('research_projects')
                ->onDelete('restrict');

            $table->uuid('funding_source_id');
            $table->foreign('funding_source_id', 'fk_ba_funding')
                ->references('id')->on('funding_sources')
                ->onDelete('restrict');

            $table->decimal('allocated_amount', 15, 2);
            $table->char('currency_code', 3)->default('LYD');
            $table->text('notes')->nullable();

            // Audit columns per SDD §4.2.6 CREATE TABLE — no soft-delete
            // columns exist on this table (documented inconsistency with
            // §4.7's soft-delete policy table, flagged in BudgetAllocation
            // Model).
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');

            $table->index('research_group_id', 'idx_ba_group');
            $table->index('project_id', 'idx_ba_project');
            $table->index('funding_source_id', 'idx_ba_funding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_allocations');
    }
};