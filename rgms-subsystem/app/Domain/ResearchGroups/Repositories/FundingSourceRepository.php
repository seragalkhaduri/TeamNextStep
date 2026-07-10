<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\FundingSource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * FundingSourceRepository
 *
 * @extends BaseRepository<FundingSource>
 */
final class FundingSourceRepository extends BaseRepository
{
    public function __construct(FundingSource $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate all funding sources — Admin/Auditor cross-group view
     * (SDD §3.5.4: GET /funding-sources).
     *
     * @param array<string, mixed> $filters
     */
    public function paginateAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['research_group_id'])) {
            $query->where('research_group_id', $filters['research_group_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * All funding sources currently Active — used by the
     * research_admin financial dashboard (FR-FUND-011).
     */
    public function findAllActive(): Collection
    {
        return $this->model->newQuery()
            ->where('status', FundingSource::STATUS_ACTIVE)
            ->get();
    }

    /**
     * Funding sources whose end_date has passed while still marked
     * Active — used by FundingService::checkThresholds() to also
     * flag expired-but-unflagged sources.
     */
    public function findExpiredButActive(): Collection
    {
        return $this->model->newQuery()
            ->where('status', FundingSource::STATUS_ACTIVE)
            ->where('end_date', '<', now()->toDateString())
            ->get();
    }
}