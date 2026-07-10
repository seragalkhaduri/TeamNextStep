<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Models\ReportExecutionHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GenerateReportRequest
 *
 * Validates input for POST /api/v1/reports/generate. Authorization
 * is delegated to ReportPolicy::generate() (SDD §3.12.4).
 *
 * SDD Reference: RGMS SDD §3.12.4, §3.12.6
 */
final class GenerateReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('generate', ReportExecutionHistory::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in([
                'ResearchGroupSummary', 'ProjectProgress', 'BudgetUtilization',
                'MembershipRoster', 'PublicationOutput', 'AssetInventory', 'ComplianceStatus',
            ])],
            'format' => ['required', Rule::in(['pdf', 'xlsx'])],
            'scope_group_ids' => ['nullable', 'array'],
            'scope_group_ids.*' => ['uuid', 'exists:research_groups,id'],
            'date_range_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_range_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after:date_range_from'],
        ];
    }
}