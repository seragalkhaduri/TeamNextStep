<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\EquipmentMaintenance;
use Illuminate\Support\Collection;

/**
 * EquipmentMaintenanceRepository
 *
 * @extends BaseRepository<EquipmentMaintenance>
 */
final class EquipmentMaintenanceRepository extends BaseRepository
{
    public function __construct(EquipmentMaintenance $model)
    {
        parent::__construct($model);
    }

    /**
     * All maintenance records for a single equipment asset, ordered
     * by scheduled_date descending (SDD §3.8.7).
     */
    public function findByEquipment(string $equipmentId): Collection
    {
        return $this->model->newQuery()
            ->where('equipment_id', $equipmentId)
            ->orderByDesc('scheduled_date')
            ->get();
    }

    /**
     * The most recent completed maintenance record for an equipment
     * asset (SDD §3.8.7).
     */
    public function getLastCompleted(string $equipmentId): ?EquipmentMaintenance
    {
        return $this->model->newQuery()
            ->where('equipment_id', $equipmentId)
            ->whereNotNull('completion_date')
            ->orderByDesc('completion_date')
            ->first();
    }
}