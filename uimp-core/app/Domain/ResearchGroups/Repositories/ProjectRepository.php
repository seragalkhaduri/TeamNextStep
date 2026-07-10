<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\ResearchProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ProjectRepository
 *
 * @extends BaseRepository<ResearchProject>
 */
final class ProjectRepository extends BaseRepository
{
    public function __construct(ResearchProject $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate projects belonging to a single research group,
     * applying status, risk_level, and date_range filters.
     *
     * @param array<string, mixed> $filters
     */
    public function findByGroup(string $groupId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters($this->model->newQuery(), $filters)
            ->where('research_group_id', $groupId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Projects whose end_date has passed while still Active.
     */
    public function findOverdueProjects(): Collection
    {
        return $this->model->newQuery()
            ->where('end_date', '<', now()->toDateString())
            ->where('status', ResearchProject::STATUS_ACTIVE)
            ->get();
    }

    /**
     * Cross-group paginated view for research_admin / auditor.
     *
     * @param array<string, mixed> $filters
     */
    public function paginateGlobal(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters($this->model->newQuery(), $filters)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a project by ID, eager-loading milestones and
     * deliverables for report generation.
     */
    public function findWithMilestones(string $id): ResearchProject
    {
        return $this->model->newQuery()
            ->with(['projectMilestones.projectDeliverables'])
            ->findOrFail($id);
    }

    /**
     * Apply the shared filter set (status, risk_level, date range)
     * to a query builder instance.
     *
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['risk_level'])) {
            $query->where('risk_status', $filters['risk_level']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('start_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('end_date', '<=', $filters['to_date']);
        }

        return $query;
    }
}