<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\EquipmentAssignment;
use App\Domain\ResearchGroups\Models\EquipmentMaintenance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * EquipmentAssignmentRepository
 *
 * @extends BaseRepository<EquipmentAssignment>
 */
final class EquipmentAssignmentRepository extends BaseRepository
{
    public function __construct(EquipmentAssignment $model)
    {
        parent::__construct($model);
    }
/**
     * Paginate all bookings for a single equipment asset, newest
     * first — used by EquipmentAssignmentController::index().
     */
    public function findByEquipmentPaginated(string $equipmentId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('equipment_id', $equipmentId)
            ->orderByDesc('start_datetime')
            ->paginate($perPage);
    }
    /**
     * Find all Confirmed bookings AND scheduled maintenance windows
     * overlapping the given time range for a piece of equipment
     * (SDD §3.9.6: standard interval overlap condition —
     * NOT (end <= start OR start >= end)).
     */
    public function findConflicting(string $equipmentId, Carbon $start, Carbon $end): Collection
    {
        $conflictingBookings = $this->model->newQuery()
            ->where('equipment_id', $equipmentId)
            ->where('status', EquipmentAssignment::STATUS_CONFIRMED)
            ->where(function ($query) use ($start, $end): void {
                $query->where('end_datetime', '>', $start)
                    ->where('start_datetime', '<', $end);
            })
            ->get();

        $conflictingMaintenance = EquipmentMaintenance::query()
            ->where('equipment_id', $equipmentId)
            ->whereNull('completion_date')
            ->where('scheduled_date', '<', $end->toDateString())
            ->get();

        return $conflictingBookings->concat($conflictingMaintenance);
    }

    /**
     * Count pending (future, Confirmed) bookings for an equipment
     * asset — used by EquipmentService::transitionToDecommissioned()
     * (FR-ASSET-011).
     */
    public function countPendingBookings(string $equipmentId): int
    {
        return $this->model->newQuery()
            ->where('equipment_id', $equipmentId)
            ->where('status', EquipmentAssignment::STATUS_CONFIRMED)
            ->where('start_datetime', '>', now())
            ->count();
    }

    /**
     * Get all pending (future, Confirmed) bookings for an equipment
     * asset — used by EquipmentService for the decommission-guard
     * conflict list and for post-maintenance notification dispatch.
     */
    public function getPendingBookings(string $equipmentId): Collection
    {
        return $this->model->newQuery()
            ->where('equipment_id', $equipmentId)
            ->where('status', EquipmentAssignment::STATUS_CONFIRMED)
            ->where('start_datetime', '>', now())
            ->get();
    }

    /**
     * All Confirmed bookings for an equipment asset over the next
     * N days — used for the availability calendar view (FR-ASSET-005
     * "next 30 days").
     */
    public function getCalendar(string $equipmentId, int $days): Collection
    {
        return $this->model->newQuery()
            ->where('equipment_id', $equipmentId)
            ->where('status', EquipmentAssignment::STATUS_CONFIRMED)
            ->whereBetween('start_datetime', [now(), now()->addDays($days)])
            ->orderBy('start_datetime')
            ->get();
    }
}