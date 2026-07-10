<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Rules\BelongsToAuthScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreProjectRequest
 *
 * Validates input for POST /api/v1/research-groups/{gid}/projects.
 * Authorization is delegated to ProjectPolicy::create() (SDD §3.3.5).
 *
 * SDD Reference: RGMS SDD §3.3.5, §3.3.12
 */
final class StoreProjectRequest extends FormRequest
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
     * Prepare the data for validation, merging the route-bound
     * research_group_id so BelongsToAuthScope and exists rules apply
     * to it consistently with §3.3.12.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'research_group_id' => $this->route('research_group')?->id,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'research_group_id' => ['required', 'uuid', 'exists:research_groups,id', new BelongsToAuthScope()],
            'title' => ['required', 'string', 'max:500'],
            'funding_agency' => ['required', 'string', 'max:300'],
            'budget' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:start_date'],
            'risk_status' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'compliance_status' => ['required', Rule::in(['Compliant', 'Under-Review', 'Non-Compliant'])],
            'risk_description' => ['nullable', 'string'],
            'mitigation_actions' => ['nullable', 'string'],
        ];
    }
}