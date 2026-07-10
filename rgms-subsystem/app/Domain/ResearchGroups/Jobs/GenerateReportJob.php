<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Jobs;

use App\Domain\ResearchGroups\Models\ReportExecutionHistory;
use App\Domain\ResearchGroups\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * GenerateReportJob
 *
 * Runs the actual PDF/XLSX generation for large-dataset reports
 * (estimated rows > 500), dispatched from ReportService::generate()
 * to avoid blocking the request/response cycle (SDD §3.12.5).
 *
 * SDD Reference: RGMS SDD §3.12.5
 */
final class GenerateReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The queue this job is dispatched on.
     *
     * @var string
     */
    public string $queue = 'reports';

    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        private readonly string $historyId,
        private readonly string $type,
        private readonly string $format,
        private readonly array $filters,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $service): void
    {
        try {
            $path = $this->format === 'pdf'
                ? $service->generatePdf($this->type, $this->filters)
                : $service->generateXlsx($this->type, $this->filters);

            ReportExecutionHistory::query()->findOrFail($this->historyId)->update([
                'status' => ReportExecutionHistory::STATUS_READY,
                'file_path' => $path,
                'file_size' => Storage::size($path),
            ]);
        } catch (\Throwable $e) {
            ReportExecutionHistory::query()->findOrFail($this->historyId)->update([
                'status' => ReportExecutionHistory::STATUS_FAILED,
            ]);

            throw $e;
        }
    }
}