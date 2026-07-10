<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Subsystems\Models\Subsystem;
use App\Domain\Subsystems\Requests\CreateSubsystemRequest;
use App\Domain\Subsystems\Requests\UpdateSubsystemRequest;
use App\Domain\Subsystems\Resources\SubsystemResource;
use App\Domain\Subsystems\Services\SubsystemService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubsystemController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected SubsystemService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, SubsystemResource::class);
    }

    public function show(string $id): JsonResponse
    {
        $subsystem = $this->service->findOrFail($id);
        return response()->json(new SubsystemResource($subsystem));
    }

    public function store(CreateSubsystemRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        return response()
            ->json(new SubsystemResource($result['subsystem'], $result['plainApiKey']), 201)
            ->header('Location', url("/api/v1/subsystems/{$result['subsystem']->id}"));
    }

    public function update(UpdateSubsystemRequest $request, string $id): JsonResponse
    {
        $subsystem = Subsystem::findOrFail($id);
        $updated = $this->service->update($subsystem, $request->validated());
        return response()->json(new SubsystemResource($updated));
    }

    public function regenerateKey(Request $request, string $id): JsonResponse
    {
        $subsystem = Subsystem::findOrFail($id);
        
        // Authorization: only SYSTEM_ADMIN or UNIVERSITY_ADMIN can regenerate keys
        abort_unless(
            $request->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN']),
            403,
            'Unauthorized'
        );

        $result = $this->service->regenerateApiKey($subsystem);

        return response()->json(new SubsystemResource($result['subsystem'], $result['plainApiKey']));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(
            $request->header('X-Confirm-Delete') === 'true',
            400,
            'X-Confirm-Delete header required.'
        );

        $subsystem = Subsystem::findOrFail($id);
        $this->service->delete($subsystem);
        return response()->json(null, 204);
    }
}
