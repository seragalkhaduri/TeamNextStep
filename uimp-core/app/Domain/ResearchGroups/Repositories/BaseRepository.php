<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * BaseRepository
 *
 * Abstract base class providing a standardized, DRY implementation of
 * common database operations (pagination, lookup, create, update,
 * soft delete, restore) with transaction wrapping. All 13 RGMS
 * repositories extend this class.
 *
 * Repositories are responsible only for CRUD, query building, search,
 * filtering, pagination, and eager loading — they MUST NOT contain
 * business logic.
 *
 * SDD Reference: RGMS SDD §3.14.1
 *
 * @template T of Model
 */
abstract class BaseRepository
{
    /**
     * @param T $model
     */
    public function __construct(protected Model $model)
    {
    }

    /**
     * Paginate the underlying model's records, applying the given
     * filters via the model's Filterable trait.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a record by its primary key or fail.
     *
     * @return T
     */
    public function findOrFail(string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new record within a database transaction.
     *
     * @param array<string, mixed> $data
     * @return T
     */
    public function create(array $data): Model
    {
        return DB::transaction(fn (): Model => $this->model->create($data));
    }

    /**
     * Update an existing record within a database transaction.
     *
     * @param T $model
     * @param array<string, mixed> $data
     * @return T
     */
    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data): Model {
            $model->update($data);

            return $model->fresh();
        });
    }

    /**
     * Soft delete a record within a database transaction.
     *
     * @param T $model
     */
    public function softDelete(Model $model): bool
    {
        return DB::transaction(fn (): bool => (bool) $model->delete());
    }

    /**
     * Restore a soft-deleted record by its primary key.
     *
     * @return T
     */
    public function restore(string $id): Model
    {
        $model = $this->model->withTrashed()->findOrFail($id);
        $model->restore();

        return $model;
    }
}