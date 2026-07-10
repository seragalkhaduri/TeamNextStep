<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Rules\ValidUimpStaffId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateResearchGroupRequest
 *
 * Validates input for PUT /api/v1/research-groups/{id}. Authorization
 * is delegated to ResearchGroupPolicy::update() — the group's PI or
 * research_admin (SDD §3.1.5).
 *
 * NOTE: status is intentionally excluded here. Lifecycle transitions
 * are governed exclusively via PATCH /research-groups/{id}/status ->
 * ResearchGroupService::transition(), which enforces the state
 * machine and justification requirement. Applying the same exclusion
 * pattern used explicitly for research_projects in the SDD.
 *
 * SDD Reference: RGMS SDD §3.1.5, §3.1.12
 */
final class UpdateResearchGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('research_group');

        return $this->user()->can('update', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $groupId = $this->route('research_group')?->id;

        return [
            'group_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('research_groups', 'group_name')
                    ->ignore($groupId)
                    ->whereNull('deleted_at'),
            ],
            'research_field' => ['sometimes', 'required', 'string', 'max:200'],
            'research_area' => ['sometimes', 'required', 'string', 'max:200'],
            'pi_staff_id' => ['sometimes', 'required', 'uuid', new ValidUimpStaffId()],
            'department_ref_id' => ['nullable', 'string', 'max:100'],
            'budget_allocation' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'funding_source_id' => ['nullable', 'uuid', 'exists:funding_sources,id'],
        ];
    }
}