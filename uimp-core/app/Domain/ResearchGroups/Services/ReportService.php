<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Jobs\GenerateReportJob;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\ReportExecutionHistory;
use App\Domain\ResearchGroups\Models\ReportSchedule;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Repositories\ReportRepository;
use App\Domain\ResearchGroups\Services\Support\PdfReportGenerator;
use App\Domain\ResearchGroups\Services\Support\XlsxReportGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * ReportService
 *
 * Implements the Reporting Engine module: RBAC-scoped report
 * generation (synchronous for small datasets, queued via
 * GenerateReportJob for datasets estimated over 500 rows), PDF/XLSX
 * generation, scheduled report execution, and schedule CRUD.
 *
 * SDD Reference: RGMS SDD §3.12.5
 */
final class ReportService
{
    private const ASYNC_ROW_THRESHOLD = 500;

    public function __construct(
        private readonly ReportRepository $repository,
        private readonly PdfReportGenerator $pdfGenerator,
        private readonly XlsxReportGenerator $xlsxGenerator,
    ) {
    }

    /**
     * Generate a report: intersects the requested group_ids with the
     * user's authorized scope, writes a Queued ReportExecutionHistory
     * record, then either dispatches GenerateReportJob (>500 estimated
     * rows) or generates inline and updates the record synchronously.
     *
     * @param array<string, mixed> $filters
     */
    public function generate(string $type, string $format, array $filters, string $userId): ReportExecutionHistory
    {
        $filters['group_ids'] = $this->intersectWithAuthorizedScope($filters['group_ids'] ?? [], $userId);

        $history = ReportExecutionHistory::create([
            'report_type' => $type,
            'format' => $format,
            'scope_config' => $filters,
            'status' => ReportExecutionHistory::STATUS_QUEUED,
            'generated_by' => $userId,
        ]);

        $estimatedRows = $this->repository->loadData($type, $filters)->count();

        if ($estimatedRows > self::ASYNC_ROW_THRESHOLD) {
            dispatch(new GenerateReportJob($history->id, $type, $format, $filters));

            return $history;
        }

        $path = $format === 'pdf'
            ? $this->generatePdf($type, $filters)
            : $this->generateXlsx($type, $filters);

        $history->update([
            'status' => ReportExecutionHistory::STATUS_READY,
            'file_path' => $path,
            'file_size' => Storage::size($path),
        ]);

        return $history->fresh();
    }

    /**
     * Generate a PDF report file and return its stored path.
     *
     * @param array<string, mixed> $filters
     */
    public function generatePdf(string $type, array $filters): string
    {
        return $this->pdfGenerator->generateAndStore($type, $filters);
    }

    /**
     * Generate an XLSX report file and return its stored path.
     *
     * @param array<string, mixed> $filters
     */
    public function generateXlsx(string $type, array $filters): string
    {
        return $this->xlsxGenerator->generateAndStore($type, $filters);
    }

    /**
     * Run a scheduled report (called by the Laravel Scheduler),
     * generating the report and dispatching SendUimpNotification with
     * the file attached as a base64 payload.
     */
    public function runScheduled(ReportSchedule $schedule): ReportExecutionHistory
    {
        $filters = $schedule->scope_config ?? [];

        $history = ReportExecutionHistory::create([
            'schedule_id' => $schedule->id,
            'report_type' => $schedule->report_type,
            'format' => $schedule->format,
            'scope_config' => $filters,
            'status' => ReportExecutionHistory::STATUS_QUEUED,
            'generated_by' => 'system-scheduler',
        ]);

        $path = $schedule->format === 'pdf'
            ? $this->generatePdf($schedule->report_type, $filters)
            : $this->generateXlsx($schedule->report_type, $filters);

        $history->update([
            'status' => ReportExecutionHistory::STATUS_READY,
            'file_path' => $path,
            'file_size' => Storage::size($path),
        ]);

        dispatch(new SendUimpNotification(
            $schedule->recipient_config['recipient_uimp_ids'] ?? [],
            'report.scheduled_ready',
            [
                'report_type' => $schedule->report_type,
                'file_base64' => base64_encode(Storage::get($path)),
                'file_name' => basename($path),
            ],
        ));

        return $history;
    }

    /**
     * Create a new report schedule.
     *
     * @param array<string, mixed> $data
     */
    public function createSchedule(array $data): ReportSchedule
    {
        return $this->repository->createSchedule([
            'report_type' => $data['report_type'],
            'format' => $data['format'],
            'frequency' => $data['frequency'],
            'scope_config' => $data['scope_group_ids'] ?? null,
            'recipient_config' => ['recipient_uimp_ids' => $data['recipient_uimp_ids']],
            'is_active' => true,
            'next_run_at' => $this->computeNextRunAt($data),
        ]);
    }

    /**
     * List all active report schedules.
     */
    public function listSchedules(): \Illuminate\Support\Collection
    {
        return $this->repository->findSchedules();
    }

    /**
     * Delete (soft delete) a report schedule.
     */
    public function deleteSchedule(ReportSchedule $schedule): bool
    {
        return $this->repository->deleteSchedule($schedule);
    }

    /**
     * Intersect requested group IDs with the user's authorized scope:
     * research_admin/auditor may access all groups; a PI is scoped to
     * groups where they are the PI (consistent with the RBAC pattern
     * established across every other module's Policy).
     *
     * @param list<string> $requestedGroupIds
     * @return list<string>
     */
    private function intersectWithAuthorizedScope(array $requestedGroupIds, string $userId): array
    {
        $user = Auth::user();

        if ($user !== null && (in_array('research_admin', $user->roles ?? [], true) || in_array('auditor', $user->roles ?? [], true))) {
            return $requestedGroupIds;
        }

        $authorizedGroupIds = ResearchGroup::query()
            ->where('pi_staff_id', $userId)
            ->pluck('id')
            ->all();

        return empty($requestedGroupIds)
            ? $authorizedGroupIds
            : array_values(array_intersect($requestedGroupIds, $authorizedGroupIds));
    }

    /**
     * Compute the next scheduled run timestamp based on frequency,
     * day_of_week (weekly), or day_of_month (monthly).
     *
     * @param array<string, mixed> $data
     */
    private function computeNextRunAt(array $data): \Illuminate\Support\Carbon
    {
        return match ($data['frequency']) {
            'daily' => now()->addDay()->startOfDay(),
            'weekly' => now()->next($data['day_of_week']),
            'monthly' => now()->addMonthNoOverflow()->setDay(min($data['day_of_month'], now()->addMonthNoOverflow()->daysInMonth)),
            default => now()->addDay(),
        };
    }
}