<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\CancelBookingRequest;
use App\Http\Requests\Rgms\StoreBookingRequest;
use App\Http\Resources\Rgms\AvailabilityCalendarResource;
use App\Http\Resources\Rgms\BookingResource;
use App\Domain\ResearchGroups\Models\EquipmentAssignment;
use App\Domain\ResearchGroups\Models\ResearchEquipment;
use App\Domain\ResearchGroups\Repositories\EquipmentAssignmentRepository;
use App\Domain\ResearchGroups\Services\EquipmentAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * EquipmentAssignmentController
 *
 * Thin controller for the Equipment Assignment and Booking
 * Management module.
 *
 * SDD Reference: RGMS SDD §3.9.3, §3.9.4
 */
final class EquipmentAssignmentController extends Controller
{
    public function __construct(
        private readonly EquipmentAssignmentService $service,
        private readonly EquipmentAssignmentRepository $repository,
    ) {
    }

    /**
     * GET /api/v1/equipment/{eid}/bookings
     */
    public function index(ResearchEquipment $equipment): JsonResponse
    {
        $this->authorize('view', $equipment);

        $bookings = $this->repository->findByEquipmentPaginated($equipment->id);

        return response()->json(['data' => BookingResource::collection($bookings)]);
    }

    /**
     * POST /api/v1/equipment/{eid}/bookings
     */
    public function store(StoreBookingRequest $request, ResearchEquipment $equipment): JsonResponse
    {
        $booking = $this->service->createBooking($equipment, $request->validated());

        return BookingResource::make($booking)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/equipment/{eid}/bookings/{bid}
     */
    public function show(ResearchEquipment $equipment, EquipmentAssignment $booking): BookingResource
    {
        $this->authorize('view', $booking);

        return BookingResource::make($booking);
    }

    /**
     * PATCH /api/v1/equipment/{eid}/bookings/{bid}/cancel
     */
    public function cancel(CancelBookingRequest $request, ResearchEquipment $equipment, EquipmentAssignment $booking): BookingResource
    {
        $updated = $this->service->cancelBooking($booking, $request->validated('cancellation_reason'));

        return BookingResource::make($updated);
    }

    /**
     * GET /api/v1/equipment/{eid}/availability
     */
    public function availability(Request $request, ResearchEquipment $equipment): AvailabilityCalendarResource
    {
        $this->authorize('availability', ResearchEquipment::class);

        $days = (int) $request->integer('days', 30);

        return AvailabilityCalendarResource::make($this->service->getAvailabilityCalendar($equipment, $days));
    }
}