<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Facilities\Models\Room;
use App\Domain\Facilities\Requests\RoomRequest;
use App\Domain\Facilities\Resources\RoomResource;
use App\Domain\Facilities\Services\RoomService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected RoomService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, RoomResource::class);
    }

    public function show(string $id): JsonResponse
    {
        $room = $this->service->findOrFail($id);
        return response()->json(new RoomResource($room));
    }

    public function store(RoomRequest $request): JsonResponse
    {
        $room = $this->service->create($request->validated());
        return response()->json(new RoomResource($room), 201)
            ->header('Location', url("/api/v1/rooms/{$room->id}"));
    }

    public function update(RoomRequest $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $updated = $this->service->update($room, $request->validated());
        return response()->json(new RoomResource($updated));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->header('X-Confirm-Delete') === 'true', 400, 'X-Confirm-Delete header required.');
        $room = Room::findOrFail($id);
        $this->service->delete($room);
        return response()->json(null, 204);
    }
}
