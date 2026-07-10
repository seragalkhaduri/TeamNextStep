<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\Patent;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * PatentRepository
 *
 * @extends BaseRepository<Patent>
 */
final class PatentRepository extends BaseRepository
{
    public function __construct(Patent $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate patents belonging to a single research group.
     *
     * @param array<string, mixed> $filters
     */
    public function findByGroup(string $groupId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('research_group_id', $groupId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}