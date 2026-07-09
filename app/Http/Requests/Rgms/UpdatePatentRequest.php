<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdatePatentRequest
 *
 * Validates input for PUT /api/v1/patents/{id}. Authorization is
 * delegated to PatentPolicy::update() — the owning group's PI or
 * research_admin (SDD §3.7.4).
 *
 * status is excluded (governed via PATCH /patents/{id}/status).
 * patent_number remains editable here since it is often unknown at
 * filing time and assigned later by the registration authority.
 *
 * SDD Reference: RGMS SDD §3.7.4, §3.7.7
 */
final class UpdatePatentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patent = $this->route('patent');

        return $this->user()->can('update', $patent);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $patentId = $this->route('patent')?->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:500'],
            'patent_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('patents', 'patent_number')->ignore($patentId)->whereNull('deleted_at'),
            ],
            'registration_authority' => ['sometimes', 'required', 'string', 'max:200'],
            'filing_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
        ];
    }
}