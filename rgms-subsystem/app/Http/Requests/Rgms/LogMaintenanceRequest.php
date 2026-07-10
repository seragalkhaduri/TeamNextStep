<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * LogMaintenanceRequest
 *
 * Validates input for POST /api/v1/equipment/{id}/maintenance.
 * Authorization is delegated to EquipmentPolicy::maintain() (SDD
 * §3.8.4).
 *
 * SDD Reference: RGMS SDD §3.8.5
 */
final class LogMaintenanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $equipment = $this->route('equipment');

        return $this->user()->can('maintain', $equipment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'maintenance_type' => ['required', Rule::in(['Preventive', 'Corrective'])],
            'scheduled_date' => ['required', 'date', 'date_format:Y-m-d'],
            'completion_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:scheduled_date'],
            'performed_by' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string'],
        ];
    }
}