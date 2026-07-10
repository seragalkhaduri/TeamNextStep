<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdatePublicationRequest
 *
 * Validates input for PUT /api/v1/publications/{id}. Authorization
 * is delegated to PublicationPolicy::update() — the owning group's
 * PI or research_admin (SDD §3.6.10... i.e. §3.6.4).
 *
 * status and citation_count are intentionally excluded: status
 * transitions are governed by the linear lifecycle in
 * PublicationService::transition() (a dedicated endpoint would be
 * used for that, consistent with the pattern established for
 * ResearchGroup/ResearchProject), and citation_count has its own
 * dedicated PATCH /publications/{id}/citations endpoint (SDD §3.6.4).
 *
 * SDD Reference: RGMS SDD §3.6.4, §3.6.6
 */
final class UpdatePublicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $publication = $this->route('publication');

        return $this->user()->can('update', $publication);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $publicationId = $this->route('publication')?->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:500'],
            'doi' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^10\.\d{4,}\//',
                \Illuminate\Validation\Rule::unique('publications', 'doi')
                    ->ignore($publicationId)
                    ->whereNull('deleted_at'),
            ],
            'journal_name' => ['nullable', 'string', 'max:300'],
            'conference_name' => ['nullable', 'string', 'max:300'],
            'issn' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}-\d{3}[\dX]$/'],
            'publisher' => ['nullable', 'string', 'max:300'],
            'impact_factor' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}