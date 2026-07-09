<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Audit\Resources\AuditLogResource;
use App\Domain\Audit\Services\AuditLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use PaginatesApiResponse;

    public function __construct(protected AuditLogService $service) {}

    /**
     * GET /api/v1/audit/logs
     *
     * Only AUDITOR or SYSTEM_ADMIN roles are authorized to query audit logs.
     */
    public function index(Request $request): JsonResponse
    {
        // Enforce compliance access controls
        abort_unless(
            $request->user()->hasAnyRole(['AUDITOR', 'SYSTEM_ADMIN']),
            403,
            'Forbidden: Only auditors and system admins can view audit trails.'
        );

        $paginator = $this->service->list($request->query());
        return $this->paginatedResponse($paginator, AuditLogResource::class);
    }
}
