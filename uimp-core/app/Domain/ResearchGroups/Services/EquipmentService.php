<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Exceptions\ConflictException;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\EquipmentMaintenance;
use App\Domain\ResearchGroups\Models\ResearchEquipment;
use App\Domain\ResearchGroups\Repositories\EquipmentAssignmentRepository;
use App\Domain\ResearchGroups\Repositories\EquipmentMaintenanceRepository;
use App\Domain\ResearchGroups\Repositories\EquipmentRepository;
use Illuminate\Support\Facades\DB;

/**
 * EquipmentService
 *
 * Implements all business rules for the Research Equipment
 * Management module: UIMP laboratory validation on registration,
 * maintenance logging with automatic Available <-> Under-Maintenance
 * status transitions, and the FR-ASSET-011 decommission guard against
 * pending confirmed bookings.
 *
 * SDD Reference: RGMS SDD §3.8.2, §3.8.6
 */
final class EquipmentService
{
    public function __construct(
        private readonly EquipmentRepository $repository,
        private readonly EquipmentMaintenanceRepository $maintenanceRepository,
        private readonly EquipmentAssignmentRepository $assignmentRepo,
    ) {
    }

    /**
     * Register a new equipment asset. Laboratory reference existence
     * is already validated by ValidUimpLaboratory at the Form
     * Request layer; this method persists the record.
     *
     * @param array<string, mixed> $data
     */
    public function create(string $groupId, array $data): ResearchEquipment
    {
        return DB::transaction(function () use ($groupId, $data): ResearchEquipment {
            $equipment = $this->repository->create([
                ...$data,
                'research_group_id' => $groupId,
                'status' => ResearchEquipment::STATUS_AVAILABLE,
            ]);

            AuditLog::record('CREATE', 'research_equipment', $equipment->id, null, $data);

            return $equipment;
        });
    }

    /**
     * Update mutable attributes of an equipment asset (status is
     * excluded — see transitionStatus()/transitionToDecommissioned()).
     *
     * @param array<string, mixed> $data
     */
    public function update(ResearchEquipment $equipment, array $data): ResearchEquipment
    {
        $oldValues = $equipment->only(array_keys($data));

        return DB::transaction(function () use ($equipment, $data, $oldValues): ResearchEquipment {
            $updated = $this->repository->update($equipment, $data);

            AuditLog::record('UPDATE', 'research_equipment', $updated->id, $oldValues, $data);

            return $updated;
        });
    }

    /**
     * Log a maintenance event for an equipment asset. If
     * scheduled_date is today or in the past and completion_date is
     * null, transitions equipment status to Under-Maintenance. If
     * completion_date is provided, transitions status back to
     * Available and notifies pending booking holders.
     *
     * @param array<string, mixed> $data
     */
    public function logMaintenance(ResearchEquipment $equipment, array $data): EquipmentMaintenance
    {
        return DB::transaction(function () use ($equipment, $data): EquipmentMaintenance {
            $record = $this->maintenanceRepository->create([
                ...$data,
                'equipment_id' => $equipment->id,
            ]);

            AuditLog::record('CREATE', 'equipment_maintenance', $record->id, null, $data);

            $scheduledDate = $record->scheduled_date;
            $completionDate = $record->completion_date;

            if ($completionDate !== null) {
                $this->repository->update($equipment, ['status' => ResearchEquipment::STATUS_AVAILABLE]);

                $pendingHolders = $this->assignmentRepo->getPendingBookings($equipment->id);

                foreach ($pendingHolders as $booking) {
                    dispatch(new SendUimpNotification(
                        [$booking->requester_uimp_id],
                        'equipment.available',
                        ['equipment_id' => $equipment->id],
                    ));
                }
            } elseif (! $scheduledDate->isFuture()) {
                $this->repository->update($equipment, ['status' => ResearchEquipment::STATUS_UNDER_MAINTENANCE]);
            }

            return $record;
        });
    }

    /**
     * Transition an equipment asset to Decommissioned, guarding
     * against pending confirmed bookings (FR-ASSET-011). Literal
     * design reference: SDD §3.8.6.
     */
    public function transitionToDecommissioned(ResearchEquipment $equipment): void
    {
        $pending = $this->assignmentRepo->countPendingBookings($equipment->id);

        if ($pending > 0) {
            throw new ConflictException(
                "Cannot decommission: {$pending} pending confirmed booking(s) exist.",
            );
        }

        DB::transaction(function () use ($equipment): void {
            $old = $equipment->status;

            $equipment->update(['status' => ResearchEquipment::STATUS_DECOMMISSIONED]);

            AuditLog::record(
                'UPDATE',
                'research_equipment',
                $equipment->id,
                ['status' => $old],
                ['status' => ResearchEquipment::STATUS_DECOMMISSIONED],
            );
        });
    }

    /**
     * Transition an equipment asset to any status other than
     * Decommissioned (which has its own guarded method).
     */
    public function transitionStatus(ResearchEquipment $equipment, string $newStatus): ResearchEquipment
    {
        if ($newStatus === ResearchEquipment::STATUS_DECOMMISSIONED) {
            $this->transitionToDecommissioned($equipment);

            return $equipment->fresh();
        }

        $oldStatus = $equipment->status;

        return DB::transaction(function () use ($equipment, $oldStatus, $newStatus): ResearchEquipment {
            $updated = $this->repository->update($equipment, ['status' => $newStatus]);

            AuditLog::record(
                'UPDATE',
                'research_equipment',
                $updated->id,
                ['status' => $oldStatus],
                ['status' => $newStatus],
            );

            return $updated;
        });
    }

    /**
     * Soft delete an equipment asset. The decommission guard (FR-ASSET-011)
     * must pass first — soft delete is only applied after the asset
     * is already Decommissioned.
     */
    public function softDelete(ResearchEquipment $equipment): bool
    {
        if ($equipment->status !== ResearchEquipment::STATUS_DECOMMISSIONED) {
            $this->transitionToDecommissioned($equipment);
        }

        return DB::transaction(function () use ($equipment): bool {
            $result = $this->repository->softDelete($equipment);

            AuditLog::record('DELETE', 'research_equipment', $equipment->id, $equipment->toArray(), null);

            return $result;
        });
    }
}