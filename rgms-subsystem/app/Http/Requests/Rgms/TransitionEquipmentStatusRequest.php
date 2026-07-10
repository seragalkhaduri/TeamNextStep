<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TransitionEquipmentStatusRequest
 *
 * Validates input for PATCH /api/v1/equipment/{id}/status.
 * Authorization is delegated to EquipmentPolicy::update() —
 * research_admin only (SDD §3.8.4). The FR-ASSET-011 decommission
 * guard is enforced by EquipmentService::transitionToDecommissioned(),
 * not here.
 *
 * SDD Reference: RGMS SDD §3.8.2, §3.8.4
 */
final class TransitionEquipmentStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $equipment = $this->route('equipment');

        return $this->user()->can('update', $equipment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'Available', 'Booked', 'Under-Maintenance', 'Decommissioned', 'In-Transit',
            ])],
        ];
    }
}