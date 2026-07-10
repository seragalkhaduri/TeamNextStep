<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Models\ResearchGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TransitionResearchGroupStatusRequest
 *
 * Validates input for PATCH /api/v1/research-groups/{id}/status.
 * Authorization is delegated to ResearchGroupPolicy::transition() —
 * research_admin only (SDD §3.1.5, §3.1.10).
 *
 * SDD Reference: RGMS SDD §3.1.5, §3.1.12
 */
final class TransitionResearchGroupStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('research_group');

        return $this->user()->can('transition', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                ResearchGroup::STATUS_DRAFT,
                ResearchGroup::STATUS_ACTIVE,
                ResearchGroup::STATUS_SUSPENDED,
                ResearchGroup::STATUS_ARCHIVED,
            ])],
            'justification' => [
                'required_if:status,' . ResearchGroup::STATUS_SUSPENDED,
                'required_if:status,' . ResearchGroup::STATUS_ARCHIVED,
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }
}