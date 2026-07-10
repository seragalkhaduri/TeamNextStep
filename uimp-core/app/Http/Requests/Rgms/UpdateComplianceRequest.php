<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateComplianceRequest
 *
 * Validates input for PUT /api/v1/compliance/{id}. Authorization is
 * delegated to CompliancePolicy::update() — research_admin only
 * (SDD §3.10.4). resolution_notes/resolved_at/resolved_by are
 * excluded — governed exclusively via PATCH /compliance/{id}/resolve.
 *
 * SDD Reference: RGMS SDD §3.10.4, §3.10.6
 */
final class UpdateComplianceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $record = $this->route('compliance');

        return $this->user()->can('update', $record);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'condition_type' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'status' => ['sometimes', 'required', Rule::in(['Compliant', 'Under-Review', 'Non-Compliant'])],
            'due_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:today'],
            'regulatory_reference' => ['nullable', 'string', 'max:300'],
        ];
    }
}