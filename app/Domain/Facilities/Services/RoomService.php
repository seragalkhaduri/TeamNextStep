<?php

namespace App\Domain\Facilities\Services;

use App\Domain\BaseService;
use App\Domain\Facilities\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoomService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Room::query()->with('building.campus');

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['buildingId'])) {
            $query->where('building_id', $filters['buildingId']);
        }
        if (!empty($filters['roomType'])) {
            $query->where('room_type', $filters['roomType']);
        }
        if (!empty($filters['availabilityStatus'])) {
            $query->where('availability_status', $filters['availabilityStatus']);
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name')->paginate($size);
    }

    public function findOrFail(string $id): Room
    {
        return Room::with('building.campus')->findOrFail($id);
    }

    public function create(array $data): Room
    {
        return Room::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'room_type' => $data['roomType'],
            'capacity' => $data['capacity'] ?? null,
            'availability_status' => $data['availabilityStatus'],
            'building_id' => $data['buildingId'],
        ]);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'room_type' => $data['roomType'],
            'capacity' => $data['capacity'] ?? null,
            'availability_status' => $data['availabilityStatus'],
            'building_id' => $data['buildingId'],
        ]);
        return $room->fresh();
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }
}
