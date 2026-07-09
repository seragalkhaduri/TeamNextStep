<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AvailabilityCalendarResource
 *
 * Wraps the structured array returned by
 * EquipmentAssignmentService::getAvailabilityCalendar().
 *
 * @property-read array{equipment_id: string, days: int, bookings: \Illuminate\Support\Collection} $resource
 *
 * SDD Reference: RGMS SDD §3.9.3, §3.9.2
 */
final class AvailabilityCalendarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'equipment_id' => $this->resource['equipment_id'],
            'days' => $this->resource['days'],
            'bookings' => BookingResource::collection($this->resource['bookings']),
        ];
    }
}