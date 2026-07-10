<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\ApproveDeliverableRequest;
use App\Http\Requests\Rgms\MarkMilestoneCompleteRequest;
use App\Http\Requests\Rgms\StoreDeliverableRequest;
use App\Http\Requests\Rgms\StoreMilestoneRequest;
use App\Http\Resources\Rgms\DeliverableResource;
use App\Http\Resources\Rgms\MilestoneResource;
use App\Domain\ResearchGroups\Models\ProjectDeliverable;
use App\Domain\ResearchGroups\Models\ProjectMilestone;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Repositories\MilestoneRepository;
use App\Domain\ResearchGroups\Services\MilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * MilestoneController
 *
 * Thin controller for the Project Milestone Management module.
 *
 * SDD Reference: RGMS SDD §3.4.3, §3.4.4
 */
final class MilestoneController extends Controller
{
    public function __construct(
        private readonly MilestoneService $service,
        private readonly MilestoneRepository $repository,
    ) {
    }

    /**
     * GET /api/v1/projects/{pid}/milestones
     */
    public function index(ResearchProject $project): JsonResponse
    {
        $this->authorize('view', $project);

        $milestones = $this->repository->findByProject($project->id);

        return response()->json([
            'data' => MilestoneResource::collection($milestones),
        ]);
    }

    /**
     * POST /api/v1/projects/{pid}/milestones
     */
    public function store(StoreMilestoneRequest $request, ResearchProject $project): JsonResponse
    {
        $milestone = $this->service->create($project->id, $request->validated());

        return MilestoneResource::make($milestone)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/projects/{pid}/milestones/{mid}
     */
    public function show(ResearchProject $project, ProjectMilestone $milestone): MilestoneResource
    {
        $this->authorize('view', $milestone);

        return MilestoneResource::make($milestone);
    }

    /**
     * PUT /api/v1/projects/{pid}/milestones/{mid}
     */
    /**
     * PUT /api/v1/projects/{pid}/milestones/{mid}
     */
    public function update(StoreMilestoneRequest $request, ResearchProject $project, ProjectMilestone $milestone): MilestoneResource
    {
        $this->authorize('update', $milestone);

        $updated = $this->service->update($milestone, $request->validated());

        return MilestoneResource::make($updated);
    }

    /**
     * PATCH /api/v1/projects/{pid}/milestones/{mid}/complete
     */
    public function markComplete(MarkMilestoneCompleteRequest $request, ResearchProject $project, ProjectMilestone $milestone): MilestoneResource
    {
        $updated = $this->service->complete($milestone, $request->validated());

        return MilestoneResource::make($updated);
    }

    /**
     * DELETE /api/v1/projects/{pid}/milestones/{mid}
     */
    public function destroy(ResearchProject $project, ProjectMilestone $milestone): Response
    {
        $this->authorize('delete', $milestone);

        $this->service->softDelete($milestone);

        return response()->noContent();
    }

    /**
     * POST /api/v1/milestones/{mid}/deliverables
     */
    public function storeDeliverable(StoreDeliverableRequest $request, ProjectMilestone $milestone): JsonResponse
    {
        $deliverable = $this->service->storeDeliverable($milestone->id, $request->validated());

        return DeliverableResource::make($deliverable)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * PATCH /api/v1/deliverables/{did}/approve
     */
    public function approveDeliverable(ApproveDeliverableRequest $request, ProjectDeliverable $deliverable): DeliverableResource
    {
        $updated = $this->service->approveDeliverable(
            $deliverable,
            $request->validated('approval_status'),
            (string) auth()->id(),
        );

        return DeliverableResource::make($updated);
    }
}