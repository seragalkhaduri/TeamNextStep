<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreFundingSourceRequest
 *
 * Validates input for POST /api/v1/funding-sources. Authorization is
 * delegated to FundingPolicy::create() — research_admin only (SDD
 * §3.5.4).
 *
 * SDD Reference: RGMS SDD §3.5.4, §3.5.6
 */
final class StoreFundingSourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\FundingSource::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'research_group_id' => ['required', 'uuid', 'exists:research_groups,id'],
            'agency_name' => ['required', 'string', 'max:300'],
            'grant_reference' => [
                'required',
                'string',
                'max:200',
                Rule::unique('funding_sources', 'grant_reference')->whereNull('deleted_at'),
            ],
            'allocated_amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => [
                'required',
                'string',
                'size:3',
                // ISO 4217 whitelist — SDD §3.5.6 gives a partial,
                // explicitly truncated example list ("USD,EUR,GBP,
                // LYD,SAR..."). Sourced from config for easy extension
                // without modifying this file.
                Rule::in(config('rgms.iso4217_currencies', ['USD', 'EUR', 'GBP', 'LYD', 'SAR'])),
            ],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}