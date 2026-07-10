<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Facilities\Models\Campus;
use App\Domain\Facilities\Requests\CampusRequest;
use App\Domain\Facilities\Resources\CampusResource;
use App\Domain\Facilities\Services\CampusService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected CampusService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, CampusResource::class);
    }

    public function show(string $id): JsonResponse
    {
        $campus = $this->service->findOrFail($id);
        return response()->json(new CampusResource($campus));
    }

    public function store(CampusRequest $request): JsonResponse
    {
        $campus = $this->service->create($request->validated());
        return response()->json(new CampusResource($campus), 201)
            ->header('Location', url("/api/v1/campuses/{$campus->id}"));
    }

    public function update(CampusRequest $request, string $id): JsonResponse
    {
        $campus = Campus::findOrFail($id);
        $updated = $this->service->update($campus, $request->validated());
        return response()->json(new CampusResource($updated));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->header('X-Confirm-Delete') === 'true', 400, 'X-Confirm-Delete header required.');
        $campus = Campus::findOrFail($id);
        $this->service->delete($campus);
        return response()->json(null, 204);
    }
}
