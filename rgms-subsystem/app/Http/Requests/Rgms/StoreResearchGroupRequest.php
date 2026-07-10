<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Rules\ValidUimpStaffId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreResearchGroupRequest
 *
 * Validates input for POST /api/v1/research-groups. Authorization is
 * delegated to ResearchGroupPolicy::create() (SDD §3.1.5).
 *
 * SDD Reference: RGMS SDD §3.1.5, §3.1.12
 */
final class StoreResearchGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ResearchGroup::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'group_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('research_groups', 'group_name')->whereNull('deleted_at'),
            ],
            'research_field' => ['required', 'string', 'max:200'],
            'research_area' => ['required', 'string', 'max:200'],
            'pi_staff_id' => ['required', 'uuid', new ValidUimpStaffId()],
            'status' => ['sometimes', 'string', Rule::in([
                ResearchGroup::STATUS_DRAFT,
                ResearchGroup::STATUS_ACTIVE,
                ResearchGroup::STATUS_SUSPENDED,
                ResearchGroup::STATUS_ARCHIVED,
            ])],
            'department_ref_id' => ['nullable', 'string', 'max:100'],
            'budget_allocation' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'funding_source_id' => ['nullable', 'uuid', 'exists:funding_sources,id'],
        ];
    }
}