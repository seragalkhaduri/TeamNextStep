<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * MarkMilestoneCompleteRequest
 *
 * Validates input for PATCH /api/v1/projects/{pid}/milestones/{mid}/complete.
 * Authorization is delegated to MilestonePolicy::complete() (SDD §3.4.4).
 *
 * SDD Reference: RGMS SDD §3.4.5, §3.4.7
 */
final class MarkMilestoneCompleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $milestone = $this->route('milestone');

        return $this->user()->can('complete', $milestone);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'completion_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'completion_notes' => ['nullable', 'string'],
        ];
    }
}