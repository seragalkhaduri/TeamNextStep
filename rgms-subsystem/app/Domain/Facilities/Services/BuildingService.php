<?php

namespace App\Domain\Facilities\Services;

use App\Domain\BaseService;
use App\Domain\Facilities\Models\Building;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildingService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Building::query()->with('campus')->withCount('rooms');

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['campusId'])) {
            $query->where('campus_id', $filters['campusId']);
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name_en')->paginate($size);
    }

    public function findOrFail(string $id): Building
    {
        return Building::with(['campus', 'rooms'])->findOrFail($id);
    }

    public function create(array $data): Building
    {
        return Building::create([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'code' => $data['code'],
            'campus_id' => $data['campusId'],
        ]);
    }

    public function update(Building $building, array $data): Building
    {
        $building->update([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'code' => $data['code'],
            'campus_id' => $data['campusId'],
        ]);
        return $building->fresh();
    }

    public function delete(Building $building): void
    {
        $building->delete();
    }
}
