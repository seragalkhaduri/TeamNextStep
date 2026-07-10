<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\StoreResearchGroupRequest;
use App\Http\Requests\Rgms\TransitionResearchGroupStatusRequest;
use App\Http\Requests\Rgms\UpdateResearchGroupRequest;
use App\Http\Resources\Rgms\ResearchGroupCollection;
use App\Http\Resources\Rgms\ResearchGroupResource;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Services\ResearchGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ResearchGroupController
 *
 * Thin controller for the Research Groups Management module. Every
 * action authorizes via the relevant Policy, delegates to
 * ResearchGroupService, and returns JSON via API Resources —
 * no business logic, no direct database queries, no validation logic.
 *
 * SDD Reference: RGMS SDD §3.1.4, §3.1.6
 */
final class ResearchGroupController extends Controller
{
    public function __construct(
        private readonly ResearchGroupService $service,
    ) {
    }

    /**
     * GET /api/v1/research-groups
     */
    public function index(Request $request): ResearchGroupCollection
    {
        $this->authorize('viewAny', ResearchGroup::class);

        $filters = $request->only([
            'field', 'area', 'status', 'pi_staff_id', 'from_date', 'to_date',
        ]);

        $perPage = (int) $request->integer('per_page', 15);

        return new ResearchGroupCollection($this->service->paginate($filters, $perPage));
    }

    /**
     * POST /api/v1/research-groups
     */
    public function store(StoreResearchGroupRequest $request): JsonResponse
    {
        $group = $this->service->create($request->validated());

        return ResearchGroupResource::make($group)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
            ->header('Location', route('research-groups.show', $group));
    }

    /**
     * GET /api/v1/research-groups/{research_group}
     */
    public function show(ResearchGroup $research_group): ResearchGroupResource
    {
        $this->authorize('view', $research_group);

        return ResearchGroupResource::make($research_group);
    }

    /**
     * PUT /api/v1/research-groups/{research_group}
     */
    public function update(UpdateResearchGroupRequest $request, ResearchGroup $research_group): ResearchGroupResource
    {
        $group = $this->service->update($research_group, $request->validated());

        return ResearchGroupResource::make($group);
    }

    /**
     * PATCH /api/v1/research-groups/{research_group}/status
     */
    public function transitionStatus(
        TransitionResearchGroupStatusRequest $request,
        ResearchGroup $research_group,
    ): ResearchGroupResource {
        $group = $this->service->transition(
            $research_group,
            $request->validated('status'),
            $request->validated('justification'),
        );

        return ResearchGroupResource::make($group);
    }

    /**
     * DELETE /api/v1/research-groups/{research_group}
     */
    public function destroy(ResearchGroup $research_group): Response
    {
        $this->authorize('delete', $research_group);

        $this->service->softDelete($research_group);

        return response()->noContent();
    }

    /**
     * GET /api/v1/research-groups/{research_group}/history
     */
    public function history(ResearchGroup $research_group): JsonResponse
    {
        $this->authorize('view', $research_group);

        return response()->json([
            'data' => $this->service->history($research_group),
        ]);
    }

    /**
     * GET /api/v1/research-groups/export
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', ResearchGroup::class);

        $filters = $request->only(['field', 'area', 'status', 'pi_staff_id', 'from_date', 'to_date']);
        $format = $request->string('format', 'pdf')->toString();

        return $this->service->export($filters, $format);
    }
}