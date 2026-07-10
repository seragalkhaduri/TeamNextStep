<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reports\Services\ReportsService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class ReportsController extends Controller
{
    public function __construct(protected ReportsService $service)
    {
        // Enforce RBAC at controller construct level
        $this->middleware(function ($request, $next) {
            abort_unless(
                $request->user() && $request->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'AUDITOR']),
                403,
                'Forbidden'
            );
            return $next($request);
        });
    }

    /**
     * GET /api/v1/reports/student-enrollment
     */
    public function studentEnrollment(Request $request)
    {
        $data = $this->service->getEnrollmentStats();

        if ($request->query('format') === 'pdf') {
            $pdf = Pdf::loadView('reports.student-enrollment', ['data' => $data, 'generatedAt' => now()->toDateTimeString()]);
            return $pdf->download('student_enrollment_report.pdf');
        }

        if ($request->query('format') === 'excel') {
            $filePath = $this->exportEnrollmentExcel($data);
            return response()->download($filePath, 'student_enrollment_report.xlsx')->deleteFileAfterSend(true);
        }

        return response()->json(['report' => 'student-enrollment', 'data' => $data]);
    }

    /**
     * GET /api/v1/reports/employee-headcount
     */
    public function employeeHeadcount(Request $request)
    {
        $data = $this->service->getEmployeeHeadcounts();

        if ($request->query('format') === 'pdf') {
            $pdf = Pdf::loadView('reports.employee-headcount', ['data' => $data, 'generatedAt' => now()->toDateTimeString()]);
            return $pdf->download('employee_headcount_report.pdf');
        }

        return response()->json(['report' => 'employee-headcount', 'data' => $data]);
    }

    /**
     * GET /api/v1/reports/room-utilization
     */
    public function roomRoomUtilization(Request $request)
    {
        $data = $this->service->getRoomUtilization();
        return response()->json(['report' => 'room-utilization', 'data' => $data]);
    }

    /**
     * GET /api/v1/reports/subsystem-activity
     */
    public function subsystemActivity(Request $request)
    {
        $data = $this->service->getSubsystemActivity();
        return response()->json(['report' => 'subsystem-activity', 'data' => $data]);
    }

    /**
     * GET /api/v1/reports/audit-summary
     */
    public function auditSummary(Request $request)
    {
        $data = $this->service->getAuditLogUserSummary();
        return response()->json(['report' => 'audit-summary', 'data' => $data]);
    }

    // ─── Excel Helper ────────────────────────────────────────────────

    protected function exportEnrollmentExcel(array $data): string
    {
        $fileName = 'student_enrollment_' . now()->format('Ymd_His') . '.xlsx';
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $filePath = $tempDir . '/' . $fileName;

        $writer = new Writer();
        $writer->openToFile($filePath);

        $writer->addRow(new Row([
            Cell::fromValue('Faculty (EN)'),
            Cell::fromValue('Faculty (AR)'),
            Cell::fromValue('Program (EN)'),
            Cell::fromValue('Program (AR)'),
            Cell::fromValue('Active Student Count')
        ]));

        foreach ($data as $faculty) {
            foreach ($faculty['programs'] as $prog) {
                $writer->addRow(new Row([
                    Cell::fromValue($faculty['facultyNameEn']),
                    Cell::fromValue($faculty['facultyNameAr']),
                    Cell::fromValue($prog['programNameEn']),
                    Cell::fromValue($prog['programNameAr']),
                    Cell::fromValue($prog['studentCount'])
                ]));
            }
        }

        $writer->close();
        return $filePath;
    }
}
