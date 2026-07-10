<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Rules;

use App\Domain\ResearchGroups\Repositories\EquipmentAssignmentRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * NoConflictingBooking
 *
 * Encapsulates the interval-overlap conflict check for equipment
 * bookings. Deliberately NOT a Laravel ValidationRule — per SDD
 * §3.9.9, conflict detection must run inside the row-locked
 * transaction in EquipmentAssignmentService::createBooking(), not at
 * the Form Request layer, to avoid a time-of-check/time-of-use race
 * between validation and the locked write.
 *
 * SDD Reference: RGMS SDD §3.9.5, §3.9.6, §3.9.9
 */
final class NoConflictingBooking
{
    public function __construct(
        private readonly EquipmentAssignmentRepository $repository,
    ) {
    }

    /**
     * Return all conflicting bookings/maintenance windows for the
     * given equipment and time range. An empty Collection means no
     * conflict.
     */
    public function conflicts(string $equipmentId, Carbon $start, Carbon $end): Collection
    {
        return $this->repository->findConflicting($equipmentId, $start, $end);
    }
}