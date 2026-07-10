<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\Publication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * PublicationRepository
 *
 * @extends BaseRepository<Publication>
 */
final class PublicationRepository extends BaseRepository
{
    public function __construct(Publication $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a non-deleted publication by DOI (BR-008 uniqueness check).
     */
    public function findByDoi(string $doi): ?Publication
    {
        return $this->model->newQuery()
            ->where('doi', $doi)
            ->first();
    }

    /**
     * Paginate publications belonging to a single research group.
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
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['publication_type'])) {
            $query->where('publication_type', $filters['publication_type']);
        }

        if (! empty($filters['publication_year'])) {
            $query->where('publication_year', $filters['publication_year']);
        }

        return $query;
    }
}