<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ApproveDeliverableRequest
 *
 * Validates input for PATCH /api/v1/deliverables/{did}/approve.
 * Authorization is delegated to MilestonePolicy::approve() (SDD
 * §3.4.4). Not explicitly named in §3.4.3's component map, but
 * required to keep validation logic out of the Controller
 * (master architectural rule).
 *
 * SDD Reference: RGMS SDD §3.4.4
 */
final class ApproveDeliverableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $deliverable = $this->route('deliverable');

        return $this->user()->can('approve', $deliverable);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'approval_status' => ['required', Rule::in(['Approved', 'Rejected'])],
        ];
    }
}