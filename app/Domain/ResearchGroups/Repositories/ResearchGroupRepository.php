<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\ResearchGroup;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ResearchGroupRepository
 *
 * @extends BaseRepository<ResearchGroup>
 */
final class ResearchGroupRepository extends BaseRepository
{
    public function __construct(ResearchGroup $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate research groups, applying dynamic WHERE clauses for
     * field, area, status, pi_staff_id, and a from_date/to_date
     * created_at range.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters($this->model->newQuery(), $filters)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a research group by ID, eager-loading its full status
     * transition history.
     */
    public function findWithHistory(string $id): ResearchGroup
    {
        return $this->model->newQuery()
            ->with('groupStatusHistory')
            ->findOrFail($id);
    }

    /**
     * Find research groups matching a PI, research field, and
     * research area, scoped to the given fiscal year (BR-007).
     */
    public function findByPiFieldArea(string $piId, string $field, string $area, int $year): Collection
    {
        return $this->model->newQuery()
            ->where('pi_staff_id', $piId)
            ->where('research_field', $field)
            ->where('research_area', $area)
            ->whereYear('created_at', $year)
            ->get();
    }

    /**
     * Count active memberships for a research group — used by
     * ResearchGroupService::softDelete() as a blocking-dependency check.
     */
    public function countActiveMemberships(string $groupId): int
    {
        return $this->model->newQuery()
            ->findOrFail($groupId)
            ->groupMemberships()
            ->where('status', \App\Models\GroupMembership::STATUS_ACTIVE)
            ->count();
    }

    /**
     * Retrieve research groups matching the given filters as an
     * unpaginated Collection, for bulk export.
     *
     * @param array<string, mixed> $filters
     */
    public function findForExport(array $filters = []): Collection
    {
        return $this->applyFilters($this->model->newQuery(), $filters)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Apply the shared filter set to a query builder instance.
     *
     * @param array<string, mixed> $filters
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if (! empty($filters['field'])) {
            $query->where('research_field', $filters['field']);
        }

        if (! empty($filters['area'])) {
            $query->where('research_area', $filters['area']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['pi_staff_id'])) {
            $query->where('pi_staff_id', $filters['pi_staff_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query;
    }
}