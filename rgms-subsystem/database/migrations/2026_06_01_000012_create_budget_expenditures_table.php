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
        Schema::create('budget_expenditures', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('project_id');
            $table->foreign('project_id', 'fk_be_project')
                ->references('id')->on('research_projects')
                ->onDelete('restrict');

            $table->uuid('funding_source_id');
            $table->foreign('funding_source_id', 'fk_be_funding')
                ->references('id')->on('funding_sources')
                ->onDelete('restrict');

            $table->uuid('allocation_id')->nullable();
            $table->foreign('allocation_id', 'fk_be_allocation')
                ->references('id')->on('budget_allocations')
                ->onDelete('restrict');

            $table->enum('category', [
                'Personnel', 'Equipment', 'Travel', 'Consumables', 'Overhead', 'Other',
            ]);
            $table->decimal('amount', 15, 2);
            $table->char('currency_code', 3)->default('LYD');
            $table->date('expenditure_date');
            $table->string('description', 500);

            $table->uuid('reference_expenditure_id')->nullable();
            $table->foreign('reference_expenditure_id', 'fk_be_reference')
                ->references('id')->on('budget_expenditures')
                ->onDelete('restrict');

            $table->string('override_authorized_by', 100)->nullable();

            // Immutable: created_at/created_by only — no updated_at
            // (SDD §4.2.7: record is never updated after insertion).
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');

            $table->index(['project_id', 'expenditure_date'], 'idx_be_project_date');
            $table->index(['funding_source_id', 'expenditure_date'], 'idx_be_funding_date');
            $table->index('allocation_id', 'idx_be_allocation');
            $table->index('category', 'idx_be_category');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE budget_expenditures ADD CONSTRAINT chk_be_amount CHECK (amount <> 0)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_expenditures');
    }
};