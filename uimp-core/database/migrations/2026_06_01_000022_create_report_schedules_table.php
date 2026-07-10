<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->enum('report_type', [
                'ResearchGroupSummary', 'ProjectProgress', 'BudgetUtilization',
                'MembershipRoster', 'PublicationOutput', 'AssetInventory', 'ComplianceStatus',
            ]);
            $table->enum('format', ['pdf', 'xlsx']);
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->json('scope_config')->nullable();
            $table->json('recipient_config');
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();

            // Standard RGMS audit columns (SDD §4.1.2), consistent with
            // §3.12.7's explicit "SoftDeletes" note for this table.
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            $table->index(['is_active', 'next_run_at'], 'idx_rs_active_next_run');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};