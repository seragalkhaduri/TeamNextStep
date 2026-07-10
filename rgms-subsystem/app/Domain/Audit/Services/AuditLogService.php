<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogService extends BaseService
{
    /**
     * Search and list system audit logs with filters (FR-AUD-002, SDD §4.3).
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::query()->with(['actorUser', 'actorSubsystem']);

        // Filter by actor (user)
        if (!empty($filters['actorUserId'])) {
            $query->where('actor_user_id', $filters['actorUserId']);
        }

        // Filter by actor (subsystem)
        if (!empty($filters['actorSubsystemId'])) {
            $query->where('actor_subsystem_id', $filters['actorSubsystemId']);
        }

        // Filter by action (CREATE, UPDATE, DELETE, etc.)
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Filter by entity type (Student, Employee, etc.)
        if (!empty($filters['entityType'])) {
            $query->where('entity_type', $filters['entityType']);
        }

        // Filter by entity ID
        if (!empty($filters['entityId'])) {
            $query->where('entity_id', $filters['entityId']);
        }

        // Filter by date range (createdAt)
        if (!empty($filters['dateFrom'])) {
            $query->where('created_at', '>=', $filters['dateFrom']);
        }
        if (!empty($filters['dateTo'])) {
            $query->where('created_at', '<=', $filters['dateTo']);
        }

        $size = min((int) ($filters['size'] ?? 50), 100);

        // Order chronologically descending by default
        return $query->orderByDesc('created_at')->paginate($size);
    }
}
