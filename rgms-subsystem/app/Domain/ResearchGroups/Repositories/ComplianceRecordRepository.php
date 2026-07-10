<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\ComplianceRecord;
use Illuminate\Support\Collection;

/**
 * ComplianceRecordRepository
 *
 * @extends BaseRepository<ComplianceRecord>
 */
final class ComplianceRecordRepository extends BaseRepository
{
    public function __construct(ComplianceRecord $model)
    {
        parent::__construct($model);
    }
/**
     * Paginate compliance records for a single project.
     */
    public function findByProjectPaginated(string $projectId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('project_id', $projectId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * All Non-Compliant or Under-Review conditions for currently
     * Active funded projects — used by the daily scheduled sweep
     * (SDD §3.10.5: evaluateProjectCompliance(), 01:30 daily).
     */
    public function findActionableConditions(): Collection
    {
        return $this->model->newQuery()
            ->whereIn('status', [
                \App\Models\ComplianceRecord::STATUS_NON_COMPLIANT,
                \App\Models\ComplianceRecord::STATUS_UNDER_REVIEW,
            ])
            ->whereHas('researchProject', function ($query): void {
                $query->where('status', \App\Models\ResearchProject::STATUS_ACTIVE);
            })
            ->get();
    }

    /**
     * Aggregate condition counts by status, for the compliance
     * dashboard (FR-AUD-011).
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        return $this->model->newQuery()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
    /**
     * Non-Compliant records for a single project — used by
     * ProjectService::checkComplianceConditions() (SDD §3.3.7).
     */
    public function findNonCompliantByProject(string $projectId): Collection
    {
        return $this->model->newQuery()
            ->where('project_id', $projectId)
            ->where('status', ComplianceRecord::STATUS_NON_COMPLIANT)
            ->get();
    }

    /**
     * All compliance records for a single project, newest first.
     */
    public function findByProject(string $projectId): Collection
    {
        return $this->model->newQuery()
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}