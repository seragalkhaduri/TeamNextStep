<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\ResearchEquipment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * EquipmentRepository
 *
 * @extends BaseRepository<ResearchEquipment>
 */
final class EquipmentRepository extends BaseRepository
{
    public function __construct(ResearchEquipment $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate equipment belonging to a single research group,
     * filtering by category, status, and laboratory_ref_id.
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
     * All equipment assigned to a specific UIMP laboratory reference.
     */
    public function findByLaboratory(string $labRefId): Collection
    {
        return $this->model->newQuery()
            ->where('laboratory_ref_id', $labRefId)
            ->get();
    }

    /**
     * Assets whose most recent completed maintenance record is older
     * than a configurable interval (estimated_useful_life_years / 10
     * in months), or which have never had a completed maintenance
     * record at all.
     */
    public function findNeedingMaintenance(): Collection
    {
        return $this->model->newQuery()
            ->whereDoesntHave('equipmentMaintenance', function ($query): void {
                $query->whereNotNull('completion_date');
            })
            ->orWhereHas('equipmentMaintenance', function ($query): void {
                $query->whereNotNull('completion_date');
            }, '=', 0)
            ->get()
            ->filter(function (ResearchEquipment $equipment): bool {
                $lastCompleted = $equipment->equipmentMaintenance()
                    ->whereNotNull('completion_date')
                    ->orderByDesc('completion_date')
                    ->first();

                if ($lastCompleted === null) {
                    return true;
                }

                $intervalMonths = $equipment->estimated_useful_life_years !== null
                    ? max(1, (int) ($equipment->estimated_useful_life_years * 12 / 10))
                    : 12;

                return $lastCompleted->completion_date->addMonths($intervalMonths)->isPast();
            });
    }
}