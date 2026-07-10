<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ProductivityQueryRequest
 *
 * Shared Form Request for GET /analytics/productivity, /trends, and
 * /comparisons (SDD §3.11.3 names a single ProductivityQueryRequest
 * for this module). Authorization is delegated per-route to the
 * matching AnalyticsPolicy method.
 *
 * period is required only for the trends() route — the literal
 * §3.11.7 table lists it as unconditionally required, but
 * productivity()/comparisons() do not bucket by period; scoped here
 * to avoid breaking those two endpoints.
 *
 * SDD Reference: RGMS SDD §3.11.4, §3.11.7
 */
final class ProductivityQueryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match ($this->route()->getName()) {
            'analytics.productivity' => $this->user()->can('viewProductivity', \App\Models\ResearchGroup::class),
            'analytics.trends' => $this->user()->can('viewTrends', \App\Models\ResearchGroup::class),
            'analytics.comparisons' => $this->user()->can('viewComparisons', \App\Models\ResearchGroup::class),
            default => false,
        };
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isTrendsRoute = $this->route()->getName() === 'analytics.trends';

        return [
            'from_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:from_date'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['uuid', 'exists:research_groups,id'],
            'research_area' => ['nullable', 'string', 'max:200'],
            'period' => [$isTrendsRoute ? 'required' : 'nullable', Rule::in(['monthly', 'quarterly', 'annual'])],
        ];
    }
}