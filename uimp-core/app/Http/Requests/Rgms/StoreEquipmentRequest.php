<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Rules\ValidUimpLaboratory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreEquipmentRequest
 *
 * Validates input for POST /api/v1/research-groups/{gid}/equipment.
 * Authorization is delegated to EquipmentPolicy::create() —
 * research_admin only (SDD §3.8.4).
 *
 * status is intentionally excluded — a new asset is always created
 * Available (SDD §3.8.6).
 *
 * SDD Reference: RGMS SDD §3.8.5
 */
final class StoreEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('research_group');

        return $this->user()->can('create', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'asset_name' => ['required', 'string', 'max:300'],
            'category' => ['required', 'string', 'max:150'],
            'manufacturer' => ['required', 'string', 'max:200'],
            'model_number' => ['required', 'string', 'max:150'],
            'serial_number' => [
                'required',
                'string',
                'max:150',
                Rule::unique('research_equipment', 'serial_number')->whereNull('deleted_at'),
            ],
            'purchase_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'replacement_value' => ['nullable', 'numeric', 'min:0'],
            'estimated_useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'laboratory_ref_id' => ['required', 'string', new ValidUimpLaboratory()],
        ];
    }
}