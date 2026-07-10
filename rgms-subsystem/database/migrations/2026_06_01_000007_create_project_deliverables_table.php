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
        Schema::create('project_deliverables', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('milestone_id');
            $table->foreign('milestone_id', 'fk_pd_milestone')
                ->references('id')->on('project_milestones')
                ->onDelete('restrict');

            $table->string('description', 500);
            $table->date('due_date');
            $table->date('submission_date')->nullable();
            $table->enum('approval_status', [
                'Pending', 'Submitted', 'Approved', 'Rejected',
            ])->default('Pending');
            $table->string('submitted_by', 100)->nullable();
            $table->string('approved_by', 100)->nullable();

            // Audit columns per SDD §4.2.14 CREATE TABLE — no deleted_by
            // column exists on this table (documented inconsistency,
            // flagged in ProjectDeliverable Model and HasAuditColumns trait).
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();

            $table->index(['milestone_id', 'approval_status'], 'idx_pdel_milestone');
            $table->index('due_date', 'idx_pdel_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_deliverables');
    }
};