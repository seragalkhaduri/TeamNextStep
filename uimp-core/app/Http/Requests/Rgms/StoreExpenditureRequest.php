<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Rules\DoesNotExceedBudget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreExpenditureRequest
 *
 * Validates input for POST /api/v1/projects/{pid}/expenditures.
 * Authorization is delegated to FundingPolicy::createExpenditure() —
 * the owning group's PI or research_admin (SDD §3.5.4).
 *
 * funding_source_id is accepted here (not route-bound) since a
 * project may draw from more than one funding source (SDD §4.1.1:
 * research_groups hasMany fundingSources).
 *
 * SDD Reference: RGMS SDD §3.5.4, §3.5.6
 */
final class StoreExpenditureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('createExpenditure', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $override = $this->boolean('override_authorization');

        return [
            'funding_source_id' => ['required', 'uuid', 'exists:funding_sources,id'],
            'allocation_id' => ['nullable', 'uuid', 'exists:budget_allocations,id'],
            'category' => ['required', Rule::in([
                'Personnel', 'Equipment', 'Travel', 'Consumables', 'Overhead', 'Other',
            ])],
            'amount' => ['required', 'numeric', 'min:0.01', new DoesNotExceedBudget($project, $override)],
            'currency_code' => ['required', 'string', 'size:3'],
            'expenditure_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:500'],
            'override_authorization' => ['sometimes', 'boolean'],
        ];
    }
}