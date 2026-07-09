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
        Schema::create('compliance_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('project_id');
            $table->foreign('project_id', 'fk_cr_project')
                ->references('id')->on('research_projects')
                ->onDelete('restrict');

            $table->string('condition_type', 150);
            $table->text('description');
            $table->date('due_date')->nullable();
            $table->enum('status', ['Compliant', 'Under-Review', 'Non-Compliant'])
                ->default('Compliant');
            $table->string('regulatory_reference', 200)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by', 100)->nullable();
            $table->timestamp('alert_dispatched_at')->nullable();

            // No soft-delete — permanent regulatory evidence (SDD §4.2.12).
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');

            $table->index(['project_id', 'status'], 'idx_cr_project_status');
            $table->index('due_date', 'idx_cr_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_records');
    }
};