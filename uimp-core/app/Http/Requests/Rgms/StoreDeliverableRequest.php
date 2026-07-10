<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreDeliverableRequest
 *
 * Validates input for POST /api/v1/milestones/{mid}/deliverables.
 * Authorization is delegated to MilestonePolicy::createDeliverable()
 * (SDD §3.4.4).
 *
 * SDD Reference: RGMS SDD §3.4.4, §3.4.8
 */
final class StoreDeliverableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $milestone = $this->route('milestone');

        return $this->user()->can('createDeliverable', $milestone);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:500'],
            'due_date' => ['required', 'date', 'date_format:Y-m-d'],
            'submission_date' => ['nullable', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }
}