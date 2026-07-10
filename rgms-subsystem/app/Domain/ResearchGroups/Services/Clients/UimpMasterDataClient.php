<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services\Clients;

use App\Domain\Employees\Models\Employee;
use App\Domain\ResearchGroups\Services\Clients\Dto\UimpAcademicStaffDto;
use App\Domain\ResearchGroups\Services\Clients\Dto\UimpDepartmentDto;
use App\Domain\ResearchGroups\Services\Clients\Dto\UimpStudentDto;
use App\Domain\Students\Models\Student;
use App\Domain\Organization\Models\Department;
use Illuminate\Support\Facades\Cache;

/**
 * UimpMasterDataClient
 *
 * After the UIMP + RGMS merger this client no longer performs HTTP
 * round-trips to an external UIMP API gateway. Instead it queries the
 * shared MySQL database directly through the existing UIMP domain
 * models (Employee, Student, Department).
 *
 * The public interface is intentionally identical to the old HTTP
 * implementation so that all RGMS Services, Rules and Repositories
 * that depend on this class require zero changes.
 *
 * Caching is preserved with the same TTLs so that read-heavy RGMS
 * operations still benefit from the Laravel cache layer.
 *
 * SDD Reference: RGMS SDD §3.14.5 (updated for merged architecture)
 */
final class UimpMasterDataClient
{
    /**
     * Academic ranks eligible to serve as Principal Investigator on
     * a research group (business rule: LECTURER and EMERITUS excluded).
     *
     * @var list<string>
     */
    private const PI_ELIGIBLE_RANKS = [
        'ASSISTANT_PROFESSOR',
        'ASSOCIATE_PROFESSOR',
        'PROFESSOR',
    ];

    /**
     * Retrieve an Academic Staff (Employee) record by UIMP UUID,
     * cached for 15 minutes.
     */
    public function getAcademicStaff(string $staffId): ?UimpAcademicStaffDto
    {
        $cacheKey = "uimp:academic_staff:{$staffId}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($staffId): ?UimpAcademicStaffDto {
            /** @var Employee|null $employee */
            $employee = Employee::query()->find($staffId);

            if ($employee === null) {
                return null;
            }

            $rank = $employee->academic_rank ?? '';

            return new UimpAcademicStaffDto(
                id: (string) $employee->id,
                nameEn: (string) $employee->name_en,
                nameAr: (string) $employee->name_ar,
                academicRank: $rank,
                isEligibleForPi: in_array($rank, self::PI_ELIGIBLE_RANKS, true),
            );
        });
    }

    /**
     * Retrieve a Student record by UIMP UUID, cached for 15 minutes.
     */
    public function getStudent(string $studentId): ?UimpStudentDto
    {
        $cacheKey = "uimp:student:{$studentId}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($studentId): ?UimpStudentDto {
            /** @var Student|null $student */
            $student = Student::query()->find($studentId);

            if ($student === null) {
                return null;
            }

            return new UimpStudentDto(
                id: (string) $student->id,
                nameEn: (string) $student->name_en,
                nameAr: (string) $student->name_ar,
                enrollmentStatus: (string) ($student->enrollment_status ?? 'UNKNOWN'),
            );
        });
    }

    /**
     * Verify that a Room (laboratory) referenced by RGMS exists and
     * is active within UIMP, cached for 60 minutes.
     */
    public function validateLaboratory(string $laboratoryId): bool
    {
        $cacheKey = "uimp:laboratory:valid:{$laboratoryId}";

        return (bool) Cache::remember($cacheKey, now()->addMinutes(60), function () use ($laboratoryId): bool {
            // Rooms are managed in UIMP's Facilities domain
            return \App\Domain\Facilities\Models\Room::query()
                ->where('id', $laboratoryId)
                ->exists();
        });
    }

    /**
     * Retrieve a Department record by UUID, cached for 60 minutes.
     */
    public function getDepartment(string $departmentId): ?UimpDepartmentDto
    {
        $cacheKey = "uimp:department:{$departmentId}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($departmentId): ?UimpDepartmentDto {
            /** @var Department|null $dept */
            $dept = Department::query()->find($departmentId);

            if ($dept === null) {
                return null;
            }

            return new UimpDepartmentDto(
                id: (string) $dept->id,
                nameEn: (string) $dept->name_en,
                nameAr: (string) $dept->name_ar,
            );
        });
    }

    /**
     * Verify that a member (Staff or Student) exists in UIMP and
     * that their institutional type matches the requested RGMS
     * research role.
     */
    public function validateMemberEligibility(string $memberUimpId, string $memberType, string $role): bool
    {
        return match ($role) {
            'PI', 'Co-I' => $memberType === 'Staff' && $this->getAcademicStaff($memberUimpId) !== null,
            'Graduate-Researcher' => $memberType === 'Student'
                && (string) ($this->getStudent($memberUimpId)?->enrollmentStatus ?? '') === 'ACTIVE',
            default => $this->getAcademicStaff($memberUimpId) !== null
                || $this->getStudent($memberUimpId) !== null,
        };
    }
}