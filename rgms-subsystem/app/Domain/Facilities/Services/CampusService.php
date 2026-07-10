<?php

namespace App\Domain\Facilities\Services;

use App\Domain\BaseService;
use App\Domain\Facilities\Models\Campus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CampusService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Campus::query()->withCount('buildings');

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%");
            });
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name_en')->paginate($size);
    }

    public function findOrFail(string $id): Campus
    {
        return Campus::with('buildings.rooms')->findOrFail($id);
    }

    public function create(array $data): Campus
    {
        return Campus::create([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'address' => $data['address'] ?? null,
        ]);
    }

    public function update(Campus $campus, array $data): Campus
    {
        $campus->update([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'address' => $data['address'] ?? null,
        ]);
        return $campus->fresh();
    }

    public function delete(Campus $campus): void
    {
        $campus->delete();
    }
}
