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
        Schema::create('project_milestones', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('project_id');
            $table->foreign('project_id', 'fk_pm_project')
                ->references('id')->on('research_projects')
                ->onDelete('restrict');

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->date('completion_date')->nullable();
            $table->enum('status', ['Pending', 'In-Progress', 'Completed', 'Overdue'])
                ->default('Pending');
            $table->text('completion_notes')->nullable();

            // Standard RGMS audit columns (SDD §4.1.2)
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            $table->index(['project_id', 'status'], 'idx_pm_project_status');
            $table->index('due_date', 'idx_pm_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};