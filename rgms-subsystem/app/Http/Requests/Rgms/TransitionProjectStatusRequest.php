<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TransitionProjectStatusRequest
 *
 * Validates input for PATCH /api/v1/projects/{id}/status.
 * Authorization is delegated to ProjectPolicy::transition() —
 * research_admin only (SDD §3.3.10).
 *
 * SDD Reference: RGMS SDD §3.3.5, §3.3.12
 */
final class TransitionProjectStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('transition', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'Planning', 'Active', 'On-Hold', 'Completed', 'Terminated',
            ])],
            'reason' => ['required_if:status,Terminated', 'string', 'min:10', 'max:1000'],
        ];
    }
}