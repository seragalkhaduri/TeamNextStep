<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\StoreProjectRequest;
use App\Http\Requests\Rgms\TransitionProjectStatusRequest;
use App\Http\Requests\Rgms\UpdateProjectRequest;
use App\Http\Resources\Rgms\ProjectResource;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Repositories\ProjectRepository;
use App\Domain\ResearchGroups\Services\ProjectService;
use App\Domain\ResearchGroups\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ProjectController
 *
 * Thin controller for the Research Projects Management module.
 * store() enforces the BR-002 readiness check via
 * ProjectService::create() (which internally validates budget and
 * group scope). transitionStatus() delegates to
 * ProjectService::transition(), which invokes
 * ProjectStateMachine::validateTransition(). generateReport()
 * delegates to ReportService::generateProjectReport() and streams a
 * PDF response.
 *
 * SDD Reference: RGMS SDD §3.3.4, §3.3.6
 */
final class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $service,
        private readonly ProjectRepository $repository,
        private readonly ReportService $reportService,
    ) {
    }

    /**
     * GET /api/v1/research-groups/{gid}/projects
     */
    public function index(Request $request, ResearchGroup $research_group): JsonResponse
    {
        $this->authorize('view', $research_group);

        $filters = $request->only(['status', 'risk_level', 'from_date', 'to_date']);
        $perPage = (int) $request->integer('per_page', 15);

        $projects = $this->repository->findByGroup($research_group->id, $filters, $perPage);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'total' => $projects->total(),
                'per_page' => $projects->perPage(),
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/research-groups/{gid}/projects
     */
    public function store(StoreProjectRequest $request, ResearchGroup $research_group): JsonResponse
    {
        $project = $this->service->create($research_group, $request->validated());

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/projects/{id}
     */
    public function show(ResearchProject $project): ProjectResource
    {
        $this->authorize('view', $project);

        return ProjectResource::make($project);
    }

    /**
     * PUT /api/v1/projects/{id}
     */
    public function update(UpdateProjectRequest $request, ResearchProject $project): ProjectResource
    {
        $updated = $this->service->update($project, $request->validated());

        return ProjectResource::make($updated);
    }

    /**
     * PATCH /api/v1/projects/{id}/status
     */
    public function transitionStatus(TransitionProjectStatusRequest $request, ResearchProject $project): ProjectResource
    {
        $updated = $this->service->transition(
            $project,
            $request->validated('status'),
            $request->validated('reason'),
        );

        return ProjectResource::make($updated);
    }

    /**
     * DELETE /api/v1/projects/{id}
     */
    public function destroy(ResearchProject $project): Response
    {
        $this->authorize('delete', $project);

        $this->service->softDelete($project);

        return response()->noContent();
    }

    /**
     * GET /api/v1/projects/{id}/report
     */
    public function generateReport(ResearchProject $project): StreamedResponse
    {
        $this->authorize('generateReport', $project);

        return $this->reportService->generateProjectReport($project);
    }

    /**
     * GET /api/v1/projects
     */
    public function globalIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ResearchProject::class);

        $filters = $request->only(['status', 'risk_level', 'from_date', 'to_date']);
        $perPage = (int) $request->integer('per_page', 15);

        $projects = $this->repository->paginateGlobal($filters, $perPage);

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'total' => $projects->total(),
                'per_page' => $projects->perPage(),
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
            ],
        ]);
    }
}