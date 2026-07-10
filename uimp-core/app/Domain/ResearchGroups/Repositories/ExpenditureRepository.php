<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\BudgetExpenditure;
use Illuminate\Support\Collection;

/**
 * ExpenditureRepository
 *
 * @extends BaseRepository<BudgetExpenditure>
 */
final class ExpenditureRepository extends BaseRepository
{
    public function __construct(BudgetExpenditure $model)
    {
        parent::__construct($model);
    }

    /**
     * All expenditure entries for a single project, newest first.
     */
    public function findByProject(string $projectId): Collection
    {
        return $this->model->newQuery()
            ->where('project_id', $projectId)
            ->orderBy('expenditure_date', 'desc')
            ->get();
    }

    /**
     * Total (net of reversals) expended amount for a single project
     * (SDD §3.5.5 — used by computeBudgetSummary()).
     */
    public function sumByProject(string $projectId): float
    {
        return (float) $this->model->newQuery()
            ->where('project_id', $projectId)
            ->sum('amount');
    }

    /**
     * Total expended amount for a single project, broken down by
     * expenditure category (SDD §3.5.5 — used by computeBudgetSummary()).
     *
     * @return array<string, float>
     */
    public function sumByCategory(string $projectId): array
    {
        return $this->model->newQuery()
            ->where('project_id', $projectId)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($total): float => (float) $total)
            ->all();
    }

    /**
     * Total expended amount across ALL projects funded by a single
     * funding source — used for funding-source-level utilization.
     */
    public function sumByFundingSource(string $fundingSourceId): float
    {
        return (float) $this->model->newQuery()
            ->where('funding_source_id', $fundingSourceId)
            ->sum('amount');
    }
}