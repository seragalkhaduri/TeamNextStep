<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateProjectRequest
 *
 * Validates input for PUT /api/v1/projects/{id}. Authorization is
 * delegated to ProjectPolicy::update() — the owning group's PI or
 * research_admin (SDD §3.3.10).
 *
 * research_group_id is intentionally excluded (SDD §3.3.5: "prohibits
 * changing research_group_id after creation"). status is also
 * excluded — governed exclusively via PATCH /projects/{id}/status ->
 * ProjectService::transition(), which enforces the state machine.
 *
 * SDD Reference: RGMS SDD §3.3.5, §3.3.12
 */
final class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('update', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:500'],
            'funding_agency' => ['sometimes', 'required', 'string', 'max:300'],
            'budget' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999.99'],
            'start_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d', 'after:start_date'],
            'risk_status' => ['sometimes', 'required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'compliance_status' => ['sometimes', 'required', Rule::in(['Compliant', 'Under-Review', 'Non-Compliant'])],
            'risk_description' => ['nullable', 'string'],
            'mitigation_actions' => ['nullable', 'string'],
        ];
    }
}