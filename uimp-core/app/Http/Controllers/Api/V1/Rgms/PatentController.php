<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\StorePatentRequest;
use App\Http\Requests\Rgms\TransitionPatentStatusRequest;
use App\Http\Requests\Rgms\UpdatePatentRequest;
use App\Http\Resources\Rgms\PatentResource;
use App\Domain\ResearchGroups\Models\Patent;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Repositories\PatentRepository;
use App\Domain\ResearchGroups\Services\PatentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * PatentController
 *
 * Thin controller for the Patents Management module.
 *
 * SDD Reference: RGMS SDD §3.7.3, §3.7.4
 */
final class PatentController extends Controller
{
    public function __construct(
        private readonly PatentService $service,
        private readonly PatentRepository $repository,
    ) {
    }

    /**
     * GET /api/v1/research-groups/{gid}/patents
     */
    public function index(Request $request, ResearchGroup $research_group): JsonResponse
    {
        $this->authorize('view', $research_group);

        $filters = $request->only(['status']);
        $perPage = (int) $request->integer('per_page', 15);

        $patents = $this->repository->findByGroup($research_group->id, $filters, $perPage);

        return response()->json([
            'data' => PatentResource::collection($patents),
            'meta' => [
                'total' => $patents->total(),
                'per_page' => $patents->perPage(),
                'current_page' => $patents->currentPage(),
                'last_page' => $patents->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/research-groups/{gid}/patents
     */
    public function store(StorePatentRequest $request, ResearchGroup $research_group): JsonResponse
    {
        $validated = $request->validated();
        $inventorUimpIds = $validated['inventor_uimp_ids'];
        unset($validated['inventor_uimp_ids']);

        $patent = $this->service->register($research_group->id, $validated, $inventorUimpIds);

        return PatentResource::make($patent)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/patents/{id}
     */
    public function show(Patent $patent): PatentResource
    {
        $this->authorize('view', $patent);

        return PatentResource::make($patent);
    }

    /**
     * PUT /api/v1/patents/{id}
     */
    public function update(UpdatePatentRequest $request, Patent $patent): PatentResource
    {
        $updated = $this->service->update($patent, $request->validated());

        return PatentResource::make($updated);
    }

    /**
     * PATCH /api/v1/patents/{id}/status
     */
    public function transitionStatus(TransitionPatentStatusRequest $request, Patent $patent): PatentResource
    {
        $updated = $this->service->transition($patent, $request->validated('status'));

        return PatentResource::make($updated);
    }

    /**
     * DELETE /api/v1/patents/{id}
     */
    public function destroy(Patent $patent): Response
    {
        $this->authorize('delete', $patent);

        $this->service->softDelete($patent);

        return response()->noContent();
    }
}