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
        Schema::create('research_projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title', 500);

            $table->uuid('research_group_id');
            $table->foreign('research_group_id', 'fk_rp_group')
                ->references('id')->on('research_groups')
                ->onDelete('restrict');

            $table->string('funding_agency', 300);
            $table->decimal('budget', 15, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', [
                'Planning', 'Active', 'On-Hold', 'Completed', 'Terminated',
            ])->default('Planning');
            $table->enum('risk_status', ['Low', 'Medium', 'High', 'Critical'])
                ->default('Low');
            $table->enum('compliance_status', [
                'Compliant', 'Under-Review', 'Non-Compliant',
            ])->default('Compliant');
            $table->text('risk_description')->nullable();
            $table->text('mitigation_actions')->nullable();

            // Standard RGMS audit columns (SDD §4.1.2)
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            $table->index(['research_group_id', 'status'], 'idx_rp_group_status');
            $table->index(['status', 'risk_status'], 'idx_rp_risk');
            $table->index('end_date', 'idx_rp_end_date');
            $table->index('compliance_status', 'idx_rp_compliance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_projects');
    }
};