<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\ComplianceRecord;
use App\Domain\ResearchGroups\Repositories\ComplianceRecordRepository;
use Illuminate\Support\Facades\DB;

/**
 * ComplianceService
 *
 * Implements the Compliance Monitoring module: condition creation
 * with immediate alerting on Non-Compliant status, resolution
 * (direct update on the existing record — per confirmed decision,
 * compliance_records is a single mutable row per condition, not an
 * append-only pattern), and the daily scheduled sweep with 24-hour
 * alert-flood prevention.
 *
 * SDD Reference: RGMS SDD §3.10.5
 */
final class ComplianceService
{
    public function __construct(
        private readonly ComplianceRecordRepository $repository,
    ) {
    }

    /**
     * Create a new compliance condition. If created directly with
     * status Non-Compliant, immediately dispatches a compliance_alert
     * notification.
     *
     * @param array<string, mixed> $data
     */
    public function create(string $projectId, array $data): ComplianceRecord
    {
        return DB::transaction(function () use ($projectId, $data): ComplianceRecord {
            $record = $this->repository->create([
                ...$data,
                'project_id' => $projectId,
            ]);

            AuditLog::record('CREATE', 'compliance_records', $record->id, null, $data);

            if ($record->status === ComplianceRecord::STATUS_NON_COMPLIANT) {
                $this->dispatchAlert($record);
            }

            return $record;
        });
    }

    /**
     * Update a compliance condition. If the status changes to
     * Non-Compliant, immediately dispatches a compliance_alert
     * notification.
     *
     * @param array<string, mixed> $data
     */
    public function update(ComplianceRecord $record, array $data): ComplianceRecord
    {
        $previousStatus = $record->status;
        $oldValues = $record->only(array_keys($data));

        return DB::transaction(function () use ($record, $data, $previousStatus, $oldValues): ComplianceRecord {
            $updated = $this->repository->update($record, $data);

            AuditLog::record('UPDATE', 'compliance_records', $updated->id, $oldValues, $data);

            if ($updated->status === ComplianceRecord::STATUS_NON_COMPLIANT && $previousStatus !== ComplianceRecord::STATUS_NON_COMPLIANT) {
                $this->dispatchAlert($updated);
            }

            return $updated;
        });
    }

    /**
     * Resolve a compliance condition: records resolution_notes and
     * resolved_by/resolved_at on the same record, and transitions
     * status to Compliant (direct update, per confirmed decision).
     */
    public function resolve(ComplianceRecord $record, string $resolutionNotes, string $resolvedBy): ComplianceRecord
    {
        $previousStatus = $record->status;

        return DB::transaction(function () use ($record, $resolutionNotes, $resolvedBy, $previousStatus): ComplianceRecord {
            $updated = $this->repository->update($record, [
                'status' => ComplianceRecord::STATUS_COMPLIANT,
                'resolution_notes' => $resolutionNotes,
                'resolved_by' => $resolvedBy,
                'resolved_at' => now(),
            ]);

            AuditLog::record(
                'UPDATE',
                'compliance_records',
                $updated->id,
                ['status' => $previousStatus],
                ['status' => ComplianceRecord::STATUS_COMPLIANT, 'resolution_notes' => $resolutionNotes],
            );

            return $updated;
        });
    }

    /**
     * Daily scheduled sweep (01:30, with withoutOverlapping() guard
     * registered in the Scheduler — SDD §3.10.7): iterates all
     * Non-Compliant/Under-Review conditions for Active funded
     * projects, dispatching compliance_alert for any Non-Compliant
     * condition that has not had an alert dispatched in the last 24
     * hours (prevents notification flooding).
     */
    public function evaluateProjectCompliance(): void
    {
        $conditions = $this->repository->findActionableConditions();

        foreach ($conditions as $record) {
            if ($record->status !== ComplianceRecord::STATUS_NON_COMPLIANT) {
                continue;
            }

            $alertedRecently = $record->alert_dispatched_at !== null
                && $record->alert_dispatched_at->gt(now()->subDay());

            if (! $alertedRecently) {
                $this->dispatchAlert($record);
            }
        }
    }

    /**
     * Aggregate compliance data for the dashboard (FR-AUD-011):
     * total conditions by status, Non-Compliant items with days
     * overdue, and Under-Review items approaching their due date.
     *
     * @return array{by_status: array<string, int>, non_compliant: list<array<string, mixed>>, approaching_due: list<array<string, mixed>>}
     */
    public function getDashboardSummary(): array
    {
        $nonCompliant = ComplianceRecord::query()
            ->where('status', ComplianceRecord::STATUS_NON_COMPLIANT)
            ->with('researchProject')
            ->get()
            ->map(fn (ComplianceRecord $record): array => [
                'id' => $record->id,
                'project_id' => $record->project_id,
                'condition_type' => $record->condition_type,
                'days_overdue' => $record->due_date?->diffInDays(now()) ?? 0,
            ])
            ->all();

        $approachingDue = ComplianceRecord::query()
            ->where('status', ComplianceRecord::STATUS_UNDER_REVIEW)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(7))
            ->get()
            ->map(fn (ComplianceRecord $record): array => [
                'id' => $record->id,
                'project_id' => $record->project_id,
                'condition_type' => $record->condition_type,
                'due_date' => $record->due_date->toDateString(),
            ])
            ->all();

        return [
            'by_status' => $this->repository->countByStatus(),
            'non_compliant' => $nonCompliant,
            'approaching_due' => $approachingDue,
        ];
    }

    /**
     * Dispatch a compliance_alert notification to the project's PI
     * and record the alert_dispatched_at timestamp to support the
     * 24-hour flood-prevention window.
     */
    private function dispatchAlert(ComplianceRecord $record): void
    {
        $this->repository->update($record, ['alert_dispatched_at' => now()]);

        dispatch(new SendUimpNotification(
            [$record->researchProject->researchGroup->pi_staff_id],
            'compliance_alert',
            ['entity_id' => $record->id, 'condition_type' => $record->condition_type],
        ));
    }
}