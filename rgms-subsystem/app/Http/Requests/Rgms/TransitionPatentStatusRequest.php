<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TransitionPatentStatusRequest
 *
 * Validates input for PATCH /api/v1/patents/{id}/status.
 * Authorization is delegated to PatentPolicy::update() (SDD §3.7.4).
 * The exact transition legality is enforced by
 * PatentService::transition() against the literal state machine
 * (SDD §3.7.5).
 *
 * SDD Reference: RGMS SDD §3.7.4, §3.7.5, §3.7.7
 */
final class TransitionPatentStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patent = $this->route('patent');

        return $this->user()->can('update', $patent);
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
                'Filed', 'Under-Examination', 'Granted', 'Rejected', 'Expired',
            ])],
            'grant_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:filing_date', 'required_if:status,Granted'],
        ];
    }
}