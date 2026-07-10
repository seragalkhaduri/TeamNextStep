<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CancelBookingRequest
 *
 * Validates input for PATCH /api/v1/equipment/{eid}/bookings/{bid}/cancel.
 * Authorization is delegated to EquipmentAssignmentPolicy::cancel()
 * (SDD §3.9.8). Not explicitly named in §3.9.3's component map, but
 * required to keep validation logic out of the Controller (master
 * architectural rule) — request schema per §5's cancellation example.
 *
 * SDD Reference: RGMS SDD §3.9.4, §3.9.8
 */
final class CancelBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $this->user()->can('cancel', $booking);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ];
    }
}