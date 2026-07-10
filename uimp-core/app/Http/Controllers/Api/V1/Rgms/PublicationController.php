<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\StorePublicationRequest;
use App\Http\Requests\Rgms\TransitionPublicationStatusRequest;
use App\Http\Requests\Rgms\UpdateCitationsRequest;
use App\Http\Requests\Rgms\UpdatePublicationRequest;
use App\Http\Resources\Rgms\PublicationResource;
use App\Domain\ResearchGroups\Models\Publication;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Repositories\PublicationRepository;
use App\Domain\ResearchGroups\Services\PublicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * PublicationController
 *
 * Thin controller for the Publications Registry module.
 *
 * SDD Reference: RGMS SDD §3.6.3, §3.6.4
 */
final class PublicationController extends Controller
{
    public function __construct(
        private readonly PublicationService $service,
        private readonly PublicationRepository $repository,
    ) {
    }

    /**
     * GET /api/v1/research-groups/{gid}/publications
     */
    public function index(Request $request, ResearchGroup $research_group): JsonResponse
    {
        $this->authorize('view', $research_group);

        $filters = $request->only(['status', 'publication_type', 'publication_year']);
        $perPage = (int) $request->integer('per_page', 15);

        $publications = $this->repository->findByGroup($research_group->id, $filters, $perPage);

        return response()->json([
            'data' => PublicationResource::collection($publications),
            'meta' => [
                'total' => $publications->total(),
                'per_page' => $publications->perPage(),
                'current_page' => $publications->currentPage(),
                'last_page' => $publications->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/research-groups/{gid}/publications
     */
    public function store(StorePublicationRequest $request, ResearchGroup $research_group): JsonResponse
    {
        $validated = $request->validated();
        $authorUimpIds = $validated['author_uimp_ids'];
        unset($validated['author_uimp_ids']);

        $publication = $this->service->register($research_group->id, $validated, $authorUimpIds);

        return PublicationResource::make($publication)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/publications/{id}
     */
    public function show(Publication $publication): PublicationResource
    {
        $this->authorize('view', $publication);

        return PublicationResource::make($publication);
    }

    /**
     * PUT /api/v1/publications/{id}
     */
    public function update(UpdatePublicationRequest $request, Publication $publication): PublicationResource
    {
        $updated = $this->service->update($publication, $request->validated());

        return PublicationResource::make($updated);
    }

    /**
     * PATCH /api/v1/publications/{id}/status
     */
    public function transitionStatus(TransitionPublicationStatusRequest $request, Publication $publication): PublicationResource
    {
        $updated = $this->service->transition($publication, $request->validated('status'));

        return PublicationResource::make($updated);
    }

    /**
     * PATCH /api/v1/publications/{id}/citations
     */
    public function updateCitations(UpdateCitationsRequest $request, Publication $publication): PublicationResource
    {
        $updated = $this->service->updateCitationCount($publication, $request->validated('citation_count'));

        return PublicationResource::make($updated);
    }

    /**
     * DELETE /api/v1/publications/{id}
     */
    public function destroy(Publication $publication): Response
    {
        $this->authorize('delete', $publication);

        $this->service->softDelete($publication);

        return response()->noContent();
    }

    /**
     * GET /api/v1/publications
     */
    public function globalIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Publication::class);

        $filters = $request->only(['status', 'publication_type', 'publication_year']);
        $perPage = (int) $request->integer('per_page', 15);

        $publications = $this->repository->paginateGlobal($filters, $perPage);

        return response()->json([
            'data' => PublicationResource::collection($publications),
            'meta' => [
                'total' => $publications->total(),
                'per_page' => $publications->perPage(),
                'current_page' => $publications->currentPage(),
                'last_page' => $publications->lastPage(),
            ],
        ]);
    }
}