<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Models\GroupMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateMembershipRequest
 *
 * Validates input for PUT /api/v1/research-groups/{gid}/members/{id}.
 * Authorization is delegated to GroupMemberPolicy::update() — group
 * PI or research_admin (SDD §3.2.10).
 *
 * member_uimp_id is intentionally excluded: a membership always
 * represents one person in one group and can never be reassigned to
 * a different UIMP member (SDD §3.2.5). role changes are still
 * validated against eligibility inside MembershipService::updateMember()
 * (which additionally verifies another PI exists before a role change
 * away from PI, per §3.2.7) — not re-run here to avoid a duplicate
 * UIMP lookup for an unchanged member_uimp_id.
 *
 * SDD Reference: RGMS SDD §3.2.5, §3.2.12
 */
final class UpdateMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $membership = $this->route('member');

        return $this->user()->can('update', $membership);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'required', Rule::in([
                GroupMembership::ROLE_PI,
                GroupMembership::ROLE_CO_I,
                GroupMembership::ROLE_RESEARCH_ASSISTANT,
                GroupMembership::ROLE_GRADUATE_RESEARCHER,
                GroupMembership::ROLE_EXTERNAL_COLLABORATOR,
            ])],
            'start_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:start_date'],
            'workload_percentage' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
        ];
    }
}