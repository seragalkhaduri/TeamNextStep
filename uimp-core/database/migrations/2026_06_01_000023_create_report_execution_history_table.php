<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_execution_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('schedule_id')->nullable();
            $table->foreign('schedule_id', 'fk_reh_schedule')
                ->references('id')->on('report_schedules')
                ->onDelete('restrict');

            $table->enum('report_type', [
                'ResearchGroupSummary', 'ProjectProgress', 'BudgetUtilization',
                'MembershipRoster', 'PublicationOutput', 'AssetInventory', 'ComplianceStatus',
            ]);
            $table->enum('format', ['pdf', 'xlsx']);
            $table->json('scope_config')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            // Status includes 'Expired' beyond the three states named
            // in §3.12.5 (Queued/Ready/Failed) — §3.12.7's daily 03:00
            // cleanup job "marks history records as Expired", which
            // requires this fourth state.
            $table->enum('status', ['Queued', 'Ready', 'Failed', 'Expired'])->default('Queued');
            $table->string('generated_by', 100);
            $table->timestamp('generated_at')->useCurrent();

            // "no SoftDeletes, permanent record" (SDD §3.12.7) — but
            // status does transition after creation (Queued -> Ready/
            // Failed -> Expired), so updated_at is needed; no full
            // audit-column set since this is a system-generated log,
            // not a user-editable entity.
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['generated_by', 'generated_at'], 'idx_reh_generated_by');
            $table->index('status', 'idx_reh_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_execution_history');
    }
};