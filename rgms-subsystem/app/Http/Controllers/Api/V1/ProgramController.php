<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organization\Models\Program;
use App\Domain\Organization\Requests\ProgramRequest;
use App\Domain\Organization\Resources\ProgramResource;
use App\Domain\Organization\Services\ProgramService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected ProgramService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, ProgramResource::class);
    }

    public function show(string $id): JsonResponse
    {
        $program = $this->service->findOrFail($id);
        return response()->json(new ProgramResource($program));
    }

    public function store(ProgramRequest $request): JsonResponse
    {
        $program = $this->service->create($request->validated());
        return response()->json(new ProgramResource($program), 201)
            ->header('Location', url("/api/v1/programs/{$program->id}"));
    }

    public function update(ProgramRequest $request, string $id): JsonResponse
    {
        $program = Program::findOrFail($id);
        $updated = $this->service->update($program, $request->validated());
        return response()->json(new ProgramResource($updated));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->header('X-Confirm-Delete') === 'true', 400, 'X-Confirm-Delete header required.');
        $program = Program::findOrFail($id);
        $this->service->delete($program);
        return response()->json(null, 204);
    }
}
