<?php

namespace App\Domain\Organization\Services;

use App\Domain\BaseService;
use App\Domain\Organization\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Department::query()->with('faculty', 'parentDepartment');

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['facultyId'])) {
            $query->where('faculty_id', $filters['facultyId']);
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name_en')->paginate($size);
    }

    public function findOrFail(string $id): Department
    {
        return Department::with(['faculty', 'parentDepartment', 'childDepartments', 'programs'])->findOrFail($id);
    }

    public function create(array $data): Department
    {
        return Department::create([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'code' => $data['code'],
            'faculty_id' => $data['facultyId'],
            'parent_department_id' => $data['parentDepartmentId'] ?? null,
        ]);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update([
            'name_en' => $data['nameEn'],
            'name_ar' => $data['nameAr'],
            'code' => $data['code'],
            'faculty_id' => $data['facultyId'],
            'parent_department_id' => $data['parentDepartmentId'] ?? null,
        ]);
        return $department->fresh();
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }
}
