<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\ResolveComplianceRequest;
use App\Http\Requests\Rgms\StoreComplianceConditionRequest;
use App\Http\Requests\Rgms\UpdateComplianceRequest;
use App\Http\Resources\Rgms\ComplianceDashboardResource;
use App\Http\Resources\Rgms\ComplianceResource;
use App\Domain\ResearchGroups\Models\ComplianceRecord;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Repositories\ComplianceRecordRepository;
use App\Domain\ResearchGroups\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * ComplianceController
 *
 * Thin controller for the Compliance Monitoring module.
 *
 * SDD Reference: RGMS SDD §3.10.3, §3.10.4
 */
final class ComplianceController extends Controller
{
    public function __construct(
        private readonly ComplianceService $service,
        private readonly ComplianceRecordRepository $repository,
    ) {
    }

    /**
     * GET /api/v1/projects/{pid}/compliance
     */
    public function index(ResearchProject $project): JsonResponse
    {
        $this->authorize('viewAny', ComplianceRecord::class);

        $records = $this->repository->findByProjectPaginated($project->id);

        return response()->json([
            'data' => ComplianceResource::collection($records),
            'meta' => [
                'total' => $records->total(),
                'per_page' => $records->perPage(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/projects/{pid}/compliance
     */
    public function store(StoreComplianceConditionRequest $request, ResearchProject $project): JsonResponse
    {
        $record = $this->service->create($project->id, $request->validated());

        return ComplianceResource::make($record)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/compliance/{id}
     */
    public function show(ComplianceRecord $compliance): ComplianceResource
    {
        $this->authorize('view', $compliance);

        return ComplianceResource::make($compliance);
    }

    /**
     * PUT /api/v1/compliance/{id}
     */
    public function update(UpdateComplianceRequest $request, ComplianceRecord $compliance): ComplianceResource
    {
        $updated = $this->service->update($compliance, $request->validated());

        return ComplianceResource::make($updated);
    }

    /**
     * PATCH /api/v1/compliance/{id}/resolve
     */
    public function resolve(ResolveComplianceRequest $request, ComplianceRecord $compliance): ComplianceResource
    {
        $updated = $this->service->resolve(
            $compliance,
            $request->validated('resolution_notes'),
            (string) Auth::id(),
        );

        return ComplianceResource::make($updated);
    }

    /**
     * GET /api/v1/compliance/dashboard
     */
    public function dashboard(): ComplianceDashboardResource
    {
        $this->authorize('viewDashboard', ComplianceRecord::class);

        return ComplianceDashboardResource::make($this->service->getDashboardSummary());
    }
}