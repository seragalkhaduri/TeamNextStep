<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Facilities\Models\Building;
use App\Domain\Facilities\Requests\BuildingRequest;
use App\Domain\Facilities\Resources\BuildingResource;
use App\Domain\Facilities\Services\BuildingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected BuildingService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, BuildingResource::class);
    }

    public function show(string $id): JsonResponse
    {
        $building = $this->service->findOrFail($id);
        return response()->json(new BuildingResource($building));
    }

    public function store(BuildingRequest $request): JsonResponse
    {
        $building = $this->service->create($request->validated());
        return response()->json(new BuildingResource($building), 201)
            ->header('Location', url("/api/v1/buildings/{$building->id}"));
    }

    public function update(BuildingRequest $request, string $id): JsonResponse
    {
        $building = Building::findOrFail($id);
        $updated = $this->service->update($building, $request->validated());
        return response()->json(new BuildingResource($updated));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->header('X-Confirm-Delete') === 'true', 400, 'X-Confirm-Delete header required.');
        $building = Building::findOrFail($id);
        $this->service->delete($building);
        return response()->json(null, 204);
    }
}
