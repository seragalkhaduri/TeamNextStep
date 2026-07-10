<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Employees\Models\Employee;
use App\Domain\Employees\Requests\CreateEmployeeRequest;
use App\Domain\Employees\Requests\UpdateEmployeeRequest;
use App\Domain\Employees\Resources\EmployeeResource;
use App\Domain\Employees\Services\EmployeeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * EmployeeController — thin controller (SDD §2 Architecture Rule 2).
 */
class EmployeeController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected EmployeeService $service) {}

    /**
     * GET /api/v1/employees
     * Query params: q, staffType, departmentId, status, page, size
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, EmployeeResource::class);
    }

    /**
     * GET /api/v1/employees/{id}
     */
    public function show(string $id): JsonResponse
    {
        $employee = $this->service->findOrFail($id);
        return response()->json(new EmployeeResource($employee));
    }

    /**
     * POST /api/v1/employees
     */
    public function store(CreateEmployeeRequest $request): JsonResponse
    {
        $employee = $this->service->create($request->validated());

        return response()
            ->json(new EmployeeResource($employee), 201)
            ->header('Location', url("/api/v1/employees/{$employee->id}"));
    }

    /**
     * PUT /api/v1/employees/{id}
     * Automatically tracks field changes in employee_history (FR-EMP-004).
     */
    public function update(UpdateEmployeeRequest $request, string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);
        $updated = $this->service->update($employee, $request->validated());
        return response()->json(new EmployeeResource($updated));
    }

    /**
     * DELETE /api/v1/employees/{id} — soft delete.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(
            $request->header('X-Confirm-Delete') === 'true',
            400,
            'X-Confirm-Delete header required.'
        );

        $employee = Employee::findOrFail($id);
        $this->service->delete($employee);
        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/employees/{id}/history
     * Returns append-only change history (FR-EMP-004).
     */
    public function history(string $id): JsonResponse
    {
        // Verify employee exists
        Employee::findOrFail($id);
        $history = $this->service->getHistory($id);

        return response()->json([
            'employeeId' => $id,
            'changes' => $history->map(fn ($h) => [
                'field' => $h->field_changed,
                'oldValue' => $h->old_value,
                'newValue' => $h->new_value,
                'changedAt' => $h->changed_at?->toIso8601String(),
            ]),
        ]);
    }
}
