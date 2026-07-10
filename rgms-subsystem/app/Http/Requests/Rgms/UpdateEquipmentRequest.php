<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Rules\ValidUimpLaboratory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateEquipmentRequest
 *
 * Validates input for PUT /api/v1/equipment/{id}. Authorization is
 * delegated to EquipmentPolicy::update() — research_admin only (SDD
 * §3.8.4).
 *
 * status is excluded (governed via PATCH /equipment/{id}/status).
 * serial_number is excluded — treated as an immutable identifier
 * once an asset is registered, consistent with how other modules
 * (e.g. ResearchGroup.group_name uniqueness) treat identity fields.
 *
 * SDD Reference: RGMS SDD §3.8.4, §3.8.5
 */
final class UpdateEquipmentRequest extends FormRequest
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
            'asset_name' => ['sometimes', 'required', 'string', 'max:300'],
            'category' => ['sometimes', 'required', 'string', 'max:150'],
            'manufacturer' => ['sometimes', 'required', 'string', 'max:200'],
            'model_number' => ['sometimes', 'required', 'string', 'max:150'],
            'acquisition_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
            'replacement_value' => ['nullable', 'numeric', 'min:0'],
            'estimated_useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'laboratory_ref_id' => ['sometimes', 'required', 'string', new ValidUimpLaboratory()],
        ];
    }
}