<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Exceptions\BookingConflictException;
use App\Domain\ResearchGroups\Exceptions\ConflictException;
use App\Domain\ResearchGroups\Exceptions\EquipmentUnavailableException;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\EquipmentAssignment;
use App\Domain\ResearchGroups\Models\ResearchEquipment;
use App\Domain\ResearchGroups\Repositories\EquipmentAssignmentRepository;
use App\Domain\ResearchGroups\Rules\NoConflictingBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * EquipmentAssignmentService
 *
 * Implements the atomic, row-locked booking creation flow
 * (FR-ASSET-005/006), cancellation, and the availability calendar
 * computation.
 *
 * NOTE: notifies the owning research group's PI in place of the
 * undefined equipment.laboratory_admin_ref_id referenced in SDD
 * §3.9.5's literal code (no such column exists in research_equipment
 * — SDD §4.2.10; flagged and substituted per direct guidance).
 *
 * SDD Reference: RGMS SDD §3.9.5, §3.9.2
 */
final class EquipmentAssignmentService
{
    public function __construct(
        private readonly EquipmentAssignmentRepository $repository,
        private readonly NoConflictingBooking $conflictChecker,
    ) {
    }

    /**
     * Create a new equipment booking. Acquires a row-level lock on
     * the equipment record before checking conflicts, to prevent two
     * concurrent requests from both passing the availability check
     * (SDD §3.9.5).
     *
     * @param array<string, mixed> $data
     */
    public function createBooking(ResearchEquipment $equipment, array $data): EquipmentAssignment
    {
        return DB::transaction(function () use ($equipment, $data): EquipmentAssignment {
            // Acquire row-level lock — prevents concurrent double-booking.
            $equipment = ResearchEquipment::query()->lockForUpdate()->findOrFail($equipment->id);

            // Guard 1: equipment must be Available or already Booked
            // (multiple non-overlapping bookings on a Booked asset
            // are permitted; overlap itself is Guard 2).
            if (! in_array($equipment->status, [
                ResearchEquipment::STATUS_AVAILABLE,
                ResearchEquipment::STATUS_BOOKED,
            ], true)) {
                throw new EquipmentUnavailableException(
                    "Equipment status '{$equipment->status}' does not allow new bookings.",
                    status: $equipment->status,
                );
            }

            // Guard 2: no overlapping confirmed booking or maintenance.
            $conflicts = $this->conflictChecker->conflicts(
                $equipment->id,
                Carbon::parse($data['start_datetime']),
                Carbon::parse($data['end_datetime']),
            );

            if ($conflicts->isNotEmpty()) {
                throw new BookingConflictException(
                    'Requested time slot conflicts with an existing booking or maintenance.',
                    $conflicts->toArray(),
                );
            }

            $requesterId = (string) Auth::id();

            $booking = $this->repository->create([
                ...$data,
                'equipment_id' => $equipment->id,
                'requester_uimp_id' => $requesterId,
                'status' => EquipmentAssignment::STATUS_CONFIRMED,
            ]);

            AuditLog::record('CREATE', 'equipment_assignments', $booking->id, null, $data);

            dispatch(new SendUimpNotification(
                [$requesterId, $equipment->researchGroup->pi_staff_id],
                'booking.confirmed',
                ['booking_id' => $booking->id, 'equipment_name' => $equipment->asset_name],
            ));

            return $booking;
        });
    }

    /**
     * Cancel a booking. The requester themselves or research_admin
     * may cancel (authorization enforced by
     * EquipmentAssignmentPolicy::cancel()).
     */
    public function cancelBooking(EquipmentAssignment $booking, ?string $reason): EquipmentAssignment
    {
        if ($booking->status !== EquipmentAssignment::STATUS_CONFIRMED) {
            throw new ConflictException('Only a Confirmed booking can be cancelled.');
        }

        return DB::transaction(function () use ($booking, $reason): EquipmentAssignment {
            $updated = $this->repository->update($booking, [
                'status' => EquipmentAssignment::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);

            AuditLog::record(
                'UPDATE',
                'equipment_assignments',
                $updated->id,
                ['status' => EquipmentAssignment::STATUS_CONFIRMED],
                ['status' => EquipmentAssignment::STATUS_CANCELLED, 'cancellation_reason' => $reason],
            );

            dispatch(new SendUimpNotification(
                [$updated->requester_uimp_id, $updated->researchEquipment->researchGroup->pi_staff_id],
                'booking.cancelled',
                ['booking_id' => $updated->id],
            ));

            return $updated;
        });
    }

    /**
     * Compute the availability calendar for an equipment asset over
     * the next N days (default 30 — FR-ASSET-005).
     *
     * @return array{equipment_id: string, days: int, bookings: \Illuminate\Support\Collection}
     */
    public function getAvailabilityCalendar(ResearchEquipment $equipment, int $days = 30): array
    {
        return [
            'equipment_id' => $equipment->id,
            'days' => $days,
            'bookings' => $this->repository->getCalendar($equipment->id, $days),
        ];
    }
}