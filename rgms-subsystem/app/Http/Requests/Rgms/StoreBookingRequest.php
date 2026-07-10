<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreBookingRequest
 *
 * Validates input for POST /api/v1/equipment/{eid}/bookings.
 * Authorization is delegated to EquipmentAssignmentPolicy::create()
 * (SDD §3.9.8).
 *
 * The conflict check (NoConflictingBooking) is deliberately excluded
 * here per §3.9.9: "applied at service layer, not Form Request, due
 * to row-lock requirement" — EquipmentAssignmentService::createBooking()
 * performs it inside a locked transaction to avoid a
 * time-of-check/time-of-use race.
 *
 * SDD Reference: RGMS SDD §3.9.9
 */
final class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $equipment = $this->route('equipment');

        return $this->user()->can('create', $equipment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_datetime' => ['required', 'date_format:Y-m-d H:i', 'after:now'],
            'end_datetime' => ['required', 'date_format:Y-m-d H:i', 'after:start_datetime'],
            'purpose' => ['required', 'string', 'max:500'],
            'requester_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}