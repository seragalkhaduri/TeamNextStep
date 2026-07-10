<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Rules\ValidUimpMemberEligibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreMembershipRequest
 *
 * Validates input for POST /api/v1/research-groups/{gid}/members.
 * Authorization is delegated to GroupMemberPolicy::addMember() (SDD
 * §3.2.10).
 *
 * SDD Reference: RGMS SDD §3.2.5, §3.2.12
 */
final class StoreMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('research_group');

        return $this->user()->can('addMember', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'member_uimp_id' => [
                'required',
                'string',
                'max:100',
                new ValidUimpMemberEligibility($this->input('role')),
            ],
            'role' => ['required', Rule::in([
                GroupMembership::ROLE_PI,
                GroupMembership::ROLE_CO_I,
                GroupMembership::ROLE_RESEARCH_ASSISTANT,
                GroupMembership::ROLE_GRADUATE_RESEARCHER,
                GroupMembership::ROLE_EXTERNAL_COLLABORATOR,
            ])],
            'start_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:start_date'],
            'workload_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}