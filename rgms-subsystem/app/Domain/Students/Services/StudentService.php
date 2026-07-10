<?php

namespace App\Domain\Students\Services;

use App\Domain\BaseService;
use App\Domain\Students\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * StudentService — business logic for student management (SDD §4).
 *
 * Key responsibilities:
 * - Dedup enforcement (FR-STU-002): 409 on duplicate (national_id, institutional_id)
 * - Full-text search across bilingual names
 * - Status tracking and program enrollment
 */
class StudentService extends BaseService
{
    /**
     * Search/list students with pagination per SDD §7.
     *
     * Query params: q, status, programId, page, size
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Student::query()->with('programs');

        // Full-text search across name_en, name_ar, institutional_id
        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('institutional_id', 'ilike', "%{$search}%")
                  ->orWhere('national_id', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filter by enrollment status
        if (!empty($filters['status'])) {
            $query->where('enrollment_status', $filters['status']);
        }

        // Filter by program
        if (!empty($filters['programId'])) {
            $query->whereHas('programs', function ($q) use ($filters) {
                $q->where('programs.id', $filters['programId']);
            });
        }

        $size = min((int) ($filters['size'] ?? 20), 100);
        return $query->orderBy('name_en')->paginate($size);
    }

    /**
     * Get students matching filters without pagination limits (for exports).
     */
    public function getFiltered(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Student::query()->with('programs');

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('institutional_id', 'ilike', "%{$search}%")
                  ->orWhere('national_id', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('enrollment_status', $filters['status']);
        }

        if (!empty($filters['programId'])) {
            $query->whereHas('programs', function ($q) use ($filters) {
                $q->where('programs.id', $filters['programId']);
            });
        }

        return $query->orderBy('name_en')->get();
    }

    /**
     * Find a student by ID with programs loaded.
     */
    public function findOrFail(string $id): Student
    {
        return Student::with('programs')->findOrFail($id);
    }

    /**
     * Create a new student.
     *
     * @throws ConflictHttpException if duplicate (national_id, institutional_id) exists (FR-STU-002)
     */
    public function create(array $data, string $createdBy): Student
    {
        // Check for dedup (FR-STU-002) — application-level check before hitting DB constraint
        $existing = Student::where('national_id', $data['nationalId'])
            ->where('institutional_id', $data['institutionalId'])
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            throw new ConflictHttpException(
                'A student with this national ID and institutional ID already exists (FR-STU-002).'
            );
        }

        return DB::transaction(function () use ($data, $createdBy) {
            $student = Student::create([
                'institutional_id' => $data['institutionalId'],
                'national_id' => $data['nationalId'],
                'name_en' => $data['nameEn'],
                'name_ar' => $data['nameAr'],
                'date_of_birth' => $data['dateOfBirth'],
                'gender' => $data['gender'],
                'nationality' => $data['nationality'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'enrollment_status' => 'ACTIVE',
                'admission_date' => $data['admissionDate'],
                'created_by' => $createdBy,
            ]);

            // Enroll in programs if provided
            if (!empty($data['programIds'])) {
                foreach ($data['programIds'] as $programId) {
                    $student->programs()->attach($programId, [
                        'id' => \Illuminate\Support\Str::uuid(),
                        'enrollment_date' => $data['admissionDate'],
                    ]);
                }
            }

            return $student->load('programs');
        });
    }

    /**
     * Update a student record.
     */
    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            $updateFields = [];

            $fieldMap = [
                'institutionalId' => 'institutional_id',
                'nationalId' => 'national_id',
                'nameEn' => 'name_en',
                'nameAr' => 'name_ar',
                'dateOfBirth' => 'date_of_birth',
                'gender' => 'gender',
                'nationality' => 'nationality',
                'email' => 'email',
                'phone' => 'phone',
                'address' => 'address',
                'enrollmentStatus' => 'enrollment_status',
                'admissionDate' => 'admission_date',
                'graduationDate' => 'graduation_date',
            ];

            foreach ($fieldMap as $camel => $snake) {
                if (array_key_exists($camel, $data)) {
                    $updateFields[$snake] = $data[$camel];
                }
            }

            if (!empty($updateFields)) {
                // Check dedup if national_id or institutional_id changed
                if (isset($updateFields['national_id']) || isset($updateFields['institutional_id'])) {
                    $natId = $updateFields['national_id'] ?? $student->national_id;
                    $instId = $updateFields['institutional_id'] ?? $student->institutional_id;

                    $duplicate = Student::where('national_id', $natId)
                        ->where('institutional_id', $instId)
                        ->where('id', '!=', $student->id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($duplicate) {
                        throw new ConflictHttpException(
                            'A student with this national ID and institutional ID already exists (FR-STU-002).'
                        );
                    }
                }

                $student->update($updateFields);
            }

            // Sync programs if provided
            if (array_key_exists('programIds', $data) && is_array($data['programIds'])) {
                $syncData = [];
                foreach ($data['programIds'] as $programId) {
                    $syncData[$programId] = [
                        'id' => \Illuminate\Support\Str::uuid(),
                        'enrollment_date' => now()->toDateString(),
                    ];
                }
                $student->programs()->sync($syncData);
            }

            return $student->fresh('programs');
        });
    }

    /**
     * Soft delete a student.
     */
    public function delete(Student $student): void
    {
        $student->delete();
    }
}
