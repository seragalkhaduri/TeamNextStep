<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ResolveComplianceRequest
 *
 * Validates input for PATCH /api/v1/compliance/{id}/resolve.
 * Authorization is delegated to CompliancePolicy::resolve() —
 * research_admin only (SDD §3.10.4).
 *
 * SDD Reference: RGMS SDD §3.10.4, §3.10.6
 */
final class ResolveComplianceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $record = $this->route('compliance');

        return $this->user()->can('resolve', $record);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolution_notes' => ['required', 'string', 'min:20', 'max:2000'],
            'resolved_at' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }
}