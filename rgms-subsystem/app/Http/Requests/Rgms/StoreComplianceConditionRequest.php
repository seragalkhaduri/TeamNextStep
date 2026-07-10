<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreComplianceConditionRequest
 *
 * Validates input for POST /api/v1/projects/{pid}/compliance.
 * Authorization is delegated to CompliancePolicy::create() —
 * research_admin only (SDD §3.10.4).
 *
 * SDD Reference: RGMS SDD §3.10.4, §3.10.6
 */
final class StoreComplianceConditionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('create', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'condition_type' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['Compliant', 'Under-Review', 'Non-Compliant'])],
            'due_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:today'],
            'regulatory_reference' => ['nullable', 'string', 'max:300'],
        ];
    }
}