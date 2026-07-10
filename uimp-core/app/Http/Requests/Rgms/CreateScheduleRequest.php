<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Models\ReportExecutionHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CreateScheduleRequest
 *
 * Validates input for POST /api/v1/reports/schedules. Authorization
 * is delegated to ReportPolicy::manageSchedules() — research_admin
 * only (SDD §3.12.4).
 *
 * SDD Reference: RGMS SDD §3.12.4, §3.12.6
 */
final class CreateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manageSchedules', ReportExecutionHistory::class);
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
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'recipient_uimp_ids' => ['required', 'array', 'min:1'],
            'day_of_week' => ['required_if:frequency,weekly', Rule::in(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'])],
            'day_of_month' => ['required_if:frequency,monthly', 'integer', 'min:1', 'max:28'],
        ];
    }
}