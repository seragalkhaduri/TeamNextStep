<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Rules\ValidGroupMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StorePublicationRequest
 *
 * Validates input for POST /api/v1/research-groups/{gid}/publications.
 * Authorization is delegated to PublicationPolicy::create() (SDD
 * §3.6.4). status is optionally accepted (defaults to
 * In-Preparation in PublicationService::register() if omitted) —
 * subsequent transitions are governed via the dedicated citations/
 * status flow, not free-form updates.
 *
 * SDD Reference: RGMS SDD §3.6.4, §3.6.6
 */
final class StorePublicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('research_group');

        return $this->user()->can('create', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $groupId = $this->route('research_group')?->id;
        $currentYear = (int) now()->format('Y');

        return [
            'title' => ['required', 'string', 'max:500'],
            'publication_type' => ['required', Rule::in([
                'Journal-Article', 'Conference-Paper', 'Book-Chapter', 'Technical-Report',
            ])],
            'publication_year' => ['required', 'integer', 'min:1900', 'max:' . ($currentYear + 1)],
            'status' => ['sometimes', Rule::in([
                'In-Preparation', 'Submitted', 'Under-Review', 'Accepted', 'Published', 'Retracted',
            ])],
            'doi' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^10\.\d{4,}\//',
                Rule::unique('publications', 'doi')->whereNull('deleted_at'),
            ],
            'journal_name' => ['nullable', 'string', 'max:300'],
            'conference_name' => ['nullable', 'string', 'max:300'],
            'issn' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}-\d{3}[\dX]$/'],
            'publisher' => ['nullable', 'string', 'max:300'],
            'impact_factor' => ['nullable', 'numeric', 'min:0'],
            'author_uimp_ids' => ['required', 'array', 'min:1'],
            'author_uimp_ids.*' => ['string', new ValidGroupMember($groupId)],
            'citation_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}