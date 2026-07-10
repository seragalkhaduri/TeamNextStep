<?php

namespace App\Domain\Reports\Services;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Employees\Models\Employee;
use App\Domain\Facilities\Models\Room;
use App\Domain\Organization\Models\Faculty;
use App\Domain\Students\Models\Student;
use App\Domain\Subsystems\Models\Subsystem;
use Illuminate\Support\Facades\DB;

class ReportsService
{
    /**
     * Report 1: Student Enrollment Statistics by program and faculty.
     */
    public function getEnrollmentStats(): array
    {
        // SQL grouping: Faculty -> Program -> active student count
        $faculties = Faculty::with(['departments.programs' => function ($q) {
            $q->withCount(['students' => function ($sq) {
                $sq->where('enrollment_status', 'ACTIVE');
            }]);
        }])->get();

        $report = [];
        foreach ($faculties as $faculty) {
            $facultyData = [
                'facultyNameEn' => $faculty->name_en,
                'facultyNameAr' => $faculty->name_ar,
                'programs' => []
            ];

            foreach ($faculty->departments as $dept) {
                foreach ($dept->programs as $prog) {
                    $facultyData['programs'][] = [
                        'programNameEn' => $prog->name_en,
                        'programNameAr' => $prog->name_ar,
                        'studentCount' => $prog->students_count
                    ];
                }
            }

            $report[] = $facultyData;
        }

        return $report;
    }

    /**
     * Report 2: Employee Headcount by department.
     */
    public function getEmployeeHeadcounts(): array
    {
        return DB::table('departments')
            ->leftJoin('employee_departments', 'departments.id', '=', 'employee_departments.department_id')
            ->leftJoin('employees', 'employee_departments.employee_id', '=', 'employees.id')
            ->select(
                'departments.id as department_id',
                'departments.name_en as name_en',
                'departments.name_ar as name_ar',
                DB::raw('count(employees.id) filter (where employees.deleted_at is null) as employee_count')
            )
            ->whereNull('departments.deleted_at')
            ->groupBy('departments.id', 'departments.name_en', 'departments.name_ar')
            ->orderBy('departments.name_en')
            ->get()
            ->toArray();
    }

    /**
     * Report 3: Room Utilization Summary.
     */
    public function getRoomUtilization(): array
    {
        return DB::table('rooms')
            ->join('buildings', 'rooms.building_id', '=', 'buildings.id')
            ->select(
                'rooms.availability_status',
                DB::raw('count(rooms.id) as room_count'),
                DB::raw('sum(rooms.capacity) as total_capacity')
            )
            ->whereNull('rooms.deleted_at')
            ->groupBy('rooms.availability_status')
            ->get()
            ->toArray();
    }

    /**
     * Report 4: Subsystem Activity Report (Audit logs count).
     */
    public function getSubsystemActivity(): array
    {
        return DB::table('subsystems')
            ->leftJoin('audit_logs', 'subsystems.id', '=', 'audit_logs.actor_subsystem_id')
            ->select(
                'subsystems.id',
                'subsystems.name_en',
                'subsystems.name_ar',
                DB::raw('count(audit_logs.id) as transaction_count')
            )
            ->whereNull('subsystems.deleted_at')
            ->groupBy('subsystems.id', 'subsystems.name_en', 'subsystems.name_ar')
            ->orderByDesc('transaction_count')
            ->get()
            ->toArray();
    }

    /**
     * Report 5: Audit Log Summary by user actor.
     */
    public function getAuditLogUserSummary(): array
    {
        return DB::table('users')
            ->join('audit_logs', 'users.id', '=', 'audit_logs.actor_user_id')
            ->select(
                'users.username',
                'audit_logs.action',
                DB::raw('count(audit_logs.id) as action_count')
            )
            ->whereNull('users.deleted_at')
            ->groupBy('users.username', 'audit_logs.action')
            ->orderBy('users.username')
            ->get()
            ->toArray();
    }
}
