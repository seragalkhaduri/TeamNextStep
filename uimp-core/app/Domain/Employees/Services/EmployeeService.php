<?php

namespace App\Domain\Employees\Services;

use App\Domain\BaseService;
use App\Domain\Employees\Models\Employee;
use App\Domain\Employees\Models\EmployeeHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * EmployeeService — business logic (SDD §4).
 *
 * Key features:
 * - Automatic field-change history tracking (FR-EMP-004)
 * - Department M:M sync
 * - Staff type discriminator validation
 */
class EmployeeService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Employee::query()->with('departments');

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('institutional_id', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['staffType'])) {
            $query->where('staff_type', $filters['staffType']);
        }

        if (!empty($filters['departmentId'])) {
            $query->whereHas('departments', function ($q) use ($filters) {
                $q->where('departments.id', $filters['departmentId']);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name_en')->paginate($size);
    }

    public function findOrFail(string $id): Employee
    {
        return Employee::with(['departments', 'history'])->findOrFail($id);
    }

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::create([
                'institutional_id' => $data['institutionalId'],
                'staff_type' => $data['staffType'],
                'name_en' => $data['nameEn'],
                'name_ar' => $data['nameAr'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'academic_rank' => $data['academicRank'] ?? null,
                'hire_date' => $data['hireDate'],
            ]);

            // Assign departments
            if (!empty($data['departmentIds'])) {
                foreach ($data['departmentIds'] as $deptId) {
                    $employee->departments()->attach($deptId, [
                        'id' => Str::uuid(),
                    ]);
                }
            }

            return $employee->load('departments');
        });
    }

    /**
     * Update employee with automatic history tracking (FR-EMP-004).
     */
    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $fieldMap = [
                'institutionalId' => 'institutional_id',
                'staffType' => 'staff_type',
                'nameEn' => 'name_en',
                'nameAr' => 'name_ar',
                'email' => 'email',
                'phone' => 'phone',
                'address' => 'address',
                'academicRank' => 'academic_rank',
                'hireDate' => 'hire_date',
                'status' => 'status',
            ];

            $updateFields = [];
            foreach ($fieldMap as $camel => $snake) {
                if (array_key_exists($camel, $data)) {
                    $updateFields[$snake] = $data[$camel];
                }
            }

            // Track changes before applying (FR-EMP-004)
            if (!empty($updateFields)) {
                foreach ($updateFields as $field => $newValue) {
                    $oldValue = $employee->getOriginal($field);
                    $newValueStr = is_null($newValue) ? null : (string) $newValue;
                    $oldValueStr = is_null($oldValue) ? null : (string) $oldValue;

                    if ($oldValueStr !== $newValueStr) {
                        EmployeeHistory::create([
                            'employee_id' => $employee->id,
                            'field_changed' => $field,
                            'old_value' => $oldValueStr,
                            'new_value' => $newValueStr,
                        ]);
                    }
                }

                $employee->update($updateFields);
            }

            // Sync departments
            if (array_key_exists('departmentIds', $data) && is_array($data['departmentIds'])) {
                $syncData = [];
                foreach ($data['departmentIds'] as $deptId) {
                    $syncData[$deptId] = ['id' => Str::uuid()];
                }
                $employee->departments()->sync($syncData);
            }

            return $employee->fresh(['departments', 'history']);
        });
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    /**
     * Get change history for an employee.
     */
    public function getHistory(string $employeeId): \Illuminate\Database\Eloquent\Collection
    {
        return EmployeeHistory::where('employee_id', $employeeId)
            ->orderByDesc('changed_at')
            ->get();
    }
}
