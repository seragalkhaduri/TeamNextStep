<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateCitationsRequest
 *
 * Validates input for PATCH /api/v1/publications/{id}/citations.
 * Authorization is delegated to PublicationPolicy::update() — the
 * owning group's PI or research_admin (SDD §3.6.4).
 *
 * SDD Reference: RGMS SDD §3.6.4, §3.6.6
 */
final class UpdateCitationsRequest extends FormRequest
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
        return [
            'citation_count' => ['required', 'integer', 'min:0'],
        ];
    }
}