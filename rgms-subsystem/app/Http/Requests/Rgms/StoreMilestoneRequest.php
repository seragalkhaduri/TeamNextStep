<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Rules\ValidMilestoneDateRange;
use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreMilestoneRequest
 *
 * Validates input for POST /api/v1/projects/{pid}/milestones.
 * Authorization is delegated to MilestonePolicy::create() (SDD
 * §3.4.4). status is intentionally excluded — new milestones are
 * always created Pending; Overdue is exclusively system-set (SDD
 * §3.4.7).
 *
 * SDD Reference: RGMS SDD §3.4.5, §3.4.7
 */
final class StoreMilestoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('create', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date', 'date_format:Y-m-d', new ValidMilestoneDateRange($project)],
        ];
    }
}