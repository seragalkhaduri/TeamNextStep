<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Requests\DepartmentRequest;
use App\Domain\Organization\Resources\DepartmentResource;
use App\Domain\Organization\Services\DepartmentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected DepartmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, DepartmentResource::class);
    }

    public function show(string $id): JsonResponse
    {
        $department = $this->service->findOrFail($id);
        return response()->json(new DepartmentResource($department));
    }

    public function store(DepartmentRequest $request): JsonResponse
    {
        $department = $this->service->create($request->validated());
        return response()->json(new DepartmentResource($department), 201)
            ->header('Location', url("/api/v1/departments/{$department->id}"));
    }

    public function update(DepartmentRequest $request, string $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $updated = $this->service->update($department, $request->validated());
        return response()->json(new DepartmentResource($updated));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->header('X-Confirm-Delete') === 'true', 400, 'X-Confirm-Delete header required.');
        $department = Department::findOrFail($id);
        $this->service->delete($department);
        return response()->json(null, 204);
    }
}
