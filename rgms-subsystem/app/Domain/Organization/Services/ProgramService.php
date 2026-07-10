<?php

namespace App\Domain\Organization\Services;

use App\Domain\BaseService;
use App\Domain\Organization\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProgramService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Program::query()->with('department.faculty');

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['departmentId'])) {
            $query->where('department_id', $filters['departmentId']);
        }

        if (!empty($filters['degreeLevel'])) {
            $query->where('degree_level', $filters['degreeLevel']);
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name_en')->paginate($size);
    }

    public function findOrFail(string $id): Program
    {
        return Program::with('department.faculty')->findOrFail($id);
    }

    public function create(array $data): Program
    {
        return Program::create([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'degree_level' => $data['degreeLevel'],
            'department_id' => $data['departmentId'],
        ]);
    }

    public function update(Program $program, array $data): Program
    {
        $program->update([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'degree_level' => $data['degreeLevel'],
            'department_id' => $data['departmentId'],
        ]);
        return $program->fresh();
    }

    public function delete(Program $program): void
    {
        $program->delete();
    }
}
