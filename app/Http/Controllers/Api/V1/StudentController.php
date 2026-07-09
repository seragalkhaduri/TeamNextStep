<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Students\Models\Student;
use App\Domain\Students\Requests\CreateStudentRequest;
use App\Domain\Students\Requests\UpdateStudentRequest;
use App\Domain\Students\Resources\StudentResource;
use App\Domain\Students\Services\StudentService;
use App\Domain\Students\Services\StudentExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * StudentController — exact endpoints from SDD §7.
 *
 * GET    /api/v1/students/{id} — single student with programs
 * POST   /api/v1/students      — create, 201 + Location, 409 duplicate
 * GET    /api/v1/students       — search with q, status, programId, pagination
 * DELETE /api/v1/students/{id} — soft delete, requires X-Confirm-Delete
 */
class StudentController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected StudentService $service) {}

    /**
     * GET /api/v1/students — search/list.
     * Query params: q, status, programId, page, size
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, StudentResource::class);
    }

    /**
     * GET /api/v1/students/{id}
     * Returns student with programs and contactInfo per SDD §7.
     */
    public function show(string $id): JsonResponse
    {
        $student = $this->service->findOrFail($id);
        return response()->json(new StudentResource($student));
    }

    /**
     * POST /api/v1/students
     * Success: 201 with Location header.
     * Errors: 409 duplicate (FR-STU-002), 422 validation.
     */
    public function store(CreateStudentRequest $request): JsonResponse
    {
        $student = $this->service->create(
            $request->validated(),
            $request->user()->id
        );

        return response()
            ->json(new StudentResource($student), 201)
            ->header('Location', url("/api/v1/students/{$student->id}"));
    }

    /**
     * PUT /api/v1/students/{id}
     */
    public function update(UpdateStudentRequest $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $updated = $this->service->update($student, $request->validated());
        return response()->json(new StudentResource($updated));
    }

    /**
     * DELETE /api/v1/students/{id} — soft delete.
     * Requires X-Confirm-Delete: true header (NFR-SAFE-002).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(
            $request->header('X-Confirm-Delete') === 'true',
            400,
            'Soft delete requires X-Confirm-Delete: true header (NFR-SAFE-002).'
        );

        $student = Student::findOrFail($id);
        $this->service->delete($student);
        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/students/export/pdf
     */
    public function exportPdf(Request $request, StudentExportService $exportService)
    {
        $students = $this->service->getFiltered($request->query());
        $filePath = $exportService->exportPdf($students);
        return response()->download($filePath, 'students_report.pdf')->deleteFileAfterSend(true);
    }

    /**
     * GET /api/v1/students/export/excel
     */
    public function exportExcel(Request $request, StudentExportService $exportService)
    {
        $students = $this->service->getFiltered($request->query());
        $filePath = $exportService->exportExcel($students);
        return response()->download($filePath, 'students_export.xlsx')->deleteFileAfterSend(true);
    }
}
