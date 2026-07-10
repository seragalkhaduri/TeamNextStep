<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\CreateScheduleRequest;
use App\Http\Requests\Rgms\GenerateReportRequest;
use App\Http\Resources\Rgms\ReportExecutionResource;
use App\Http\Resources\Rgms\ReportScheduleResource;
use App\Domain\ResearchGroups\Models\ReportExecutionHistory;
use App\Domain\ResearchGroups\Models\ReportSchedule;
use App\Domain\ResearchGroups\Repositories\ReportRepository;
use App\Domain\ResearchGroups\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * ReportController
 *
 * Thin controller for the Reporting Engine module.
 *
 * SDD Reference: RGMS SDD §3.12.3, §3.12.4
 */
final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $service,
        private readonly ReportRepository $repository,
    ) {
    }

    /**
     * POST /api/v1/reports/generate
     */
    public function generate(GenerateReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $history = $this->service->generate(
            $validated['report_type'],
            $validated['format'],
            [
                'group_ids' => $validated['scope_group_ids'] ?? [],
                'from_date' => $validated['date_range_from'] ?? null,
                'to_date' => $validated['date_range_to'] ?? null,
            ],
            (string) Auth::id(),
        );

        return ReportExecutionResource::make($history)
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * GET /api/v1/reports/history
     */
    public function history(): JsonResponse
    {
        $this->authorize('viewHistory', ReportExecutionHistory::class);

        $history = $this->repository->paginateHistory();

        return response()->json([
            'data' => ReportExecutionResource::collection($history),
            'meta' => [
                'total' => $history->total(),
                'per_page' => $history->perPage(),
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/reports/{id}/download
     */
    public function download(ReportExecutionHistory $report)
    {
        $this->authorize('download', $report);

        if ($report->status !== ReportExecutionHistory::STATUS_READY || $report->file_path === null) {
            return response()->json(['error' => 'report_not_ready'], 409);
        }

        return Storage::download($report->file_path);
    }

    /**
     * POST /api/v1/reports/schedules
     */
    public function createSchedule(CreateScheduleRequest $request): JsonResponse
    {
        $schedule = $this->service->createSchedule($request->validated());

        return ReportScheduleResource::make($schedule)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/reports/schedules
     */
    public function listSchedules(): JsonResponse
    {
        $this->authorize('manageSchedules', ReportExecutionHistory::class);

        return response()->json([
            'data' => ReportScheduleResource::collection($this->service->listSchedules()),
        ]);
    }

    /**
     * DELETE /api/v1/reports/schedules/{id}
     */
    public function deleteSchedule(ReportSchedule $schedule): Response
    {
        $this->authorize('manageSchedules', ReportExecutionHistory::class);

        $this->service->deleteSchedule($schedule);

        return response()->noContent();
    }
}