<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\CampusController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\FacultyController;
use App\Http\Controllers\Api\V1\ProgramController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\SubsystemController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\ReportsController;

// ── RGMS Controllers ─────────────────────────────────────────────────────────
use App\Http\Controllers\Api\V1\Rgms\AnalyticsController;
use App\Http\Controllers\Api\V1\Rgms\ComplianceController;
use App\Http\Controllers\Api\V1\Rgms\DashboardController;
use App\Http\Controllers\Api\V1\Rgms\EquipmentAssignmentController;
use App\Http\Controllers\Api\V1\Rgms\EquipmentController;
use App\Http\Controllers\Api\V1\Rgms\FundingController;
use App\Http\Controllers\Api\V1\Rgms\GroupMemberController;
use App\Http\Controllers\Api\V1\Rgms\MilestoneController;
use App\Http\Controllers\Api\V1\Rgms\PatentController;
use App\Http\Controllers\Api\V1\Rgms\ProjectController;
use App\Http\Controllers\Api\V1\Rgms\PublicationController;
use App\Http\Controllers\Api\V1\Rgms\ReportController;
use App\Http\Controllers\Api\V1\Rgms\ResearchGroupController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — UIMP v1 + RGMS
|--------------------------------------------------------------------------
|
| Base path: /api/v1/ (set in bootstrap/app.php)
|
| UIMP routes: /api/v1/...
| RGMS routes: /api/v1/rgms/...
|
*/

// ─── Public (no auth required) ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('password-reset/request', [AuthController::class, 'requestPasswordReset']);
    Route::post('password-reset/confirm', [AuthController::class, 'confirmPasswordReset']);
});

// ─── UIMP Protected (Sanctum auth required) ──────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth management
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::put('roles/{userId}', [AuthController::class, 'updateRoles']);
    });

    // Phase 3: Organization
    Route::apiResource('faculties', FacultyController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('programs', ProgramController::class);

    // Phase 3: Facilities
    Route::apiResource('campuses', CampusController::class);
    Route::apiResource('buildings', BuildingController::class);
    Route::apiResource('rooms', RoomController::class);

    // Phase 4: Students
    Route::get('students/export/pdf', [StudentController::class, 'exportPdf']);
    Route::get('students/export/excel', [StudentController::class, 'exportExcel']);
    Route::apiResource('students', StudentController::class);

    // Phase 5: Employees
    Route::get('employees/{employee}/history', [EmployeeController::class, 'history']);
    Route::apiResource('employees', EmployeeController::class);

    // Phase 6: Subsystems
    Route::post('subsystems/{subsystem}/regenerate-key', [SubsystemController::class, 'regenerateKey']);
    Route::apiResource('subsystems', SubsystemController::class);

    // Phase 8: Audit logs (AUDITOR/SYSTEM_ADMIN only)
    Route::get('audit/logs', [AuditLogController::class, 'index']);

    // Phase 9: Reports
    Route::get('reports/student-enrollment', [ReportsController::class, 'studentEnrollment']);
    Route::get('reports/employee-headcount', [ReportsController::class, 'employeeHeadcount']);
    Route::get('reports/room-utilization', [ReportsController::class, 'roomRoomUtilization']);
    Route::get('reports/subsystem-activity', [ReportsController::class, 'subsystemActivity']);
    Route::get('reports/audit-summary', [ReportsController::class, 'auditSummary']);
});

// ─── RGMS — Research Groups Management System ────────────────────────────────
// All RGMS routes share the /rgms prefix and require Sanctum auth.
// After the UIMP+RGMS merger the same Sanctum tokens issued by UIMP's
// AuthController are reused here — no separate JWT flow needed.
Route::prefix('rgms')->middleware(['auth:sanctum', 'rgms.auth'])->group(function (): void {

    // ── Module 3.1: Research Groups Management ────────────────────────
    Route::get('research-groups', [ResearchGroupController::class, 'index']);
    Route::post('research-groups', [ResearchGroupController::class, 'store']);
    Route::get('research-groups/export', [ResearchGroupController::class, 'export']);
    Route::get('research-groups/{research_group}', [ResearchGroupController::class, 'show']);
    Route::put('research-groups/{research_group}', [ResearchGroupController::class, 'update']);
    Route::patch('research-groups/{research_group}/status', [ResearchGroupController::class, 'transitionStatus']);
    Route::delete('research-groups/{research_group}', [ResearchGroupController::class, 'destroy']);
    Route::get('research-groups/{research_group}/history', [ResearchGroupController::class, 'history']);

    // ── Module 3.2: Membership Management ──────────────────────────────
    Route::get('research-groups/{research_group}/members', [GroupMemberController::class, 'index']);
    Route::post('research-groups/{research_group}/members', [GroupMemberController::class, 'store']);
    Route::get('research-groups/{research_group}/members/history', [GroupMemberController::class, 'memberHistory']);
    Route::get('research-groups/{research_group}/members/export', [GroupMemberController::class, 'exportRoster']);
    Route::get('research-groups/{research_group}/members/{member}', [GroupMemberController::class, 'show']);
    Route::put('research-groups/{research_group}/members/{member}', [GroupMemberController::class, 'update']);
    Route::delete('research-groups/{research_group}/members/{member}', [GroupMemberController::class, 'destroy']);

    // ── Module 3.3: Research Projects Management ───────────────────────
    Route::get('projects', [ProjectController::class, 'globalIndex']);
    Route::get('research-groups/{research_group}/projects', [ProjectController::class, 'index']);
    Route::post('research-groups/{research_group}/projects', [ProjectController::class, 'store']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::put('projects/{project}', [ProjectController::class, 'update']);
    Route::patch('projects/{project}/status', [ProjectController::class, 'transitionStatus']);
    Route::delete('projects/{project}', [ProjectController::class, 'destroy']);
    Route::get('projects/{project}/report', [ProjectController::class, 'generateReport']);

    // ── Module 3.4: Project Milestone Management ───────────────────────
    Route::get('projects/{project}/milestones', [MilestoneController::class, 'index']);
    Route::post('projects/{project}/milestones', [MilestoneController::class, 'store']);
    Route::get('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'show']);
    Route::put('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update']);
    Route::patch('projects/{project}/milestones/{milestone}/complete', [MilestoneController::class, 'markComplete']);
    Route::delete('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy']);
    Route::post('milestones/{milestone}/deliverables', [MilestoneController::class, 'storeDeliverable']);
    Route::patch('deliverables/{deliverable}/approve', [MilestoneController::class, 'approveDeliverable']);

    // ── Module 3.5: Funding Source and Budget Monitoring ────────────────
    Route::get('funding-sources', [FundingController::class, 'index']);
    Route::post('funding-sources', [FundingController::class, 'store']);
    Route::get('funding-sources/dashboard', [FundingController::class, 'dashboard']);
    Route::get('funding-sources/{funding_source}', [FundingController::class, 'show']);
    Route::put('funding-sources/{funding_source}', [FundingController::class, 'update']);
    Route::post('projects/{project}/expenditures', [FundingController::class, 'storeExpenditure']);
    Route::get('projects/{project}/expenditures', [FundingController::class, 'listExpenditures']);
    Route::get('projects/{project}/budget-summary', [FundingController::class, 'budgetSummary']);

    // ── Module 3.6: Publications Registry ──────────────────────────────
    Route::get('publications', [PublicationController::class, 'globalIndex']);
    Route::get('research-groups/{research_group}/publications', [PublicationController::class, 'index']);
    Route::post('research-groups/{research_group}/publications', [PublicationController::class, 'store']);
    Route::get('publications/{publication}', [PublicationController::class, 'show']);
    Route::put('publications/{publication}', [PublicationController::class, 'update']);
    Route::patch('publications/{publication}/status', [PublicationController::class, 'transitionStatus']);
    Route::patch('publications/{publication}/citations', [PublicationController::class, 'updateCitations']);
    Route::delete('publications/{publication}', [PublicationController::class, 'destroy']);

    // ── Module 3.7: Patents Management ─────────────────────────────────
    Route::get('research-groups/{research_group}/patents', [PatentController::class, 'index']);
    Route::post('research-groups/{research_group}/patents', [PatentController::class, 'store']);
    Route::get('patents/{patent}', [PatentController::class, 'show']);
    Route::put('patents/{patent}', [PatentController::class, 'update']);
    Route::patch('patents/{patent}/status', [PatentController::class, 'transitionStatus']);
    Route::delete('patents/{patent}', [PatentController::class, 'destroy']);

    // ── Module 3.8: Research Equipment Management ──────────────────────
    Route::get('equipment', [EquipmentController::class, 'globalIndex']);
    Route::get('research-groups/{research_group}/equipment', [EquipmentController::class, 'index']);
    Route::post('research-groups/{research_group}/equipment', [EquipmentController::class, 'store']);
    Route::get('equipment/{equipment}', [EquipmentController::class, 'show']);
    Route::put('equipment/{equipment}', [EquipmentController::class, 'update']);
    Route::patch('equipment/{equipment}/status', [EquipmentController::class, 'transitionStatus']);
    Route::post('equipment/{equipment}/maintenance', [EquipmentController::class, 'logMaintenance']);
    Route::get('equipment/{equipment}/maintenance', [EquipmentController::class, 'maintenanceLog']);
    Route::delete('equipment/{equipment}', [EquipmentController::class, 'destroy']);

    // ── Module 3.9: Equipment Assignment and Booking Management ────────
    Route::get('equipment/{equipment}/bookings', [EquipmentAssignmentController::class, 'index']);
    Route::post('equipment/{equipment}/bookings', [EquipmentAssignmentController::class, 'store']);
    Route::get('equipment/{equipment}/availability', [EquipmentAssignmentController::class, 'availability']);
    Route::get('equipment/{equipment}/bookings/{booking}', [EquipmentAssignmentController::class, 'show']);
    Route::patch('equipment/{equipment}/bookings/{booking}/cancel', [EquipmentAssignmentController::class, 'cancel']);

    // ── Module 3.10: Compliance Monitoring ──────────────────────────────
    Route::get('compliance/dashboard', [ComplianceController::class, 'dashboard']);
    Route::get('projects/{project}/compliance', [ComplianceController::class, 'index']);
    Route::post('projects/{project}/compliance', [ComplianceController::class, 'store']);
    Route::get('compliance/{compliance}', [ComplianceController::class, 'show']);
    Route::put('compliance/{compliance}', [ComplianceController::class, 'update']);
    Route::patch('compliance/{compliance}/resolve', [ComplianceController::class, 'resolve']);

    // ── Module 3.11: Research Productivity Analytics (read-only) ───────
    Route::get('analytics/productivity', [AnalyticsController::class, 'productivity'])->name('rgms.analytics.productivity');
    Route::get('analytics/trends', [AnalyticsController::class, 'trends'])->name('rgms.analytics.trends');
    Route::get('analytics/comparisons', [AnalyticsController::class, 'comparisons'])->name('rgms.analytics.comparisons');
    Route::get('analytics/research-groups/{research_group}', [AnalyticsController::class, 'groupStats']);

    // ── Module 3.12: Reporting Engine ───────────────────────────────────
    Route::post('reports/generate', [ReportController::class, 'generate']);
    Route::get('reports/history', [ReportController::class, 'history']);
    Route::get('reports/{report}/download', [ReportController::class, 'download']);
    Route::post('reports/schedules', [ReportController::class, 'createSchedule']);
    Route::get('reports/schedules', [ReportController::class, 'listSchedules']);
    Route::delete('reports/schedules/{schedule}', [ReportController::class, 'deleteSchedule']);

    // ── Module 3.13: Dashboard Management ─────────────────────────────
    Route::get('dashboard/pi/{research_group}', [DashboardController::class, 'pi']);
    Route::get('dashboard/admin', [DashboardController::class, 'admin']);
    Route::get('dashboard/auditor', [DashboardController::class, 'auditor']);
    Route::get('dashboard/sysadmin', [DashboardController::class, 'sysAdmin']);
});
