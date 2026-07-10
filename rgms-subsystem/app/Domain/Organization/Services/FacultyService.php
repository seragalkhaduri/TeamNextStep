<?php

namespace App\Domain\Organization\Services;

use App\Domain\BaseService;
use App\Domain\Organization\Models\Faculty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FacultyService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Faculty::query()->withCount('departments');

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name_en')->paginate($size);
    }

    public function findOrFail(string $id): Faculty
    {
        return Faculty::with('departments.programs')->findOrFail($id);
    }

    public function create(array $data): Faculty
    {
        return Faculty::create([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'code' => $data['code'],
        ]);
    }

    public function update(Faculty $faculty, array $data): Faculty
    {
        $faculty->update([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'code' => $data['code'],
        ]);
        return $faculty->fresh();
    }

    public function delete(Faculty $faculty): void
    {
        $faculty->delete(); // Soft delete
    }
}
