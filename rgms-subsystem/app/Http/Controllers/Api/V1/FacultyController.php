<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organization\Models\Faculty;
use App\Domain\Organization\Requests\FacultyRequest;
use App\Domain\Organization\Resources\FacultyResource;
use App\Domain\Organization\Services\FacultyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected FacultyService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, FacultyResource::class);
    }

    public function show(string $id): JsonResponse
    {
        $faculty = $this->service->findOrFail($id);
        return response()->json(new FacultyResource($faculty));
    }

    public function store(FacultyRequest $request): JsonResponse
    {
        $faculty = $this->service->create($request->validated());
        return response()->json(new FacultyResource($faculty), 201)
            ->header('Location', url("/api/v1/faculties/{$faculty->id}"));
    }

    public function update(FacultyRequest $request, string $id): JsonResponse
    {
        $faculty = Faculty::findOrFail($id);
        $updated = $this->service->update($faculty, $request->validated());
        return response()->json(new FacultyResource($updated));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->header('X-Confirm-Delete') === 'true', 400, 'X-Confirm-Delete header required.');
        $faculty = Faculty::findOrFail($id);
        $this->service->delete($faculty);
        return response()->json(null, 204);
    }
}
