<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TransitionPublicationStatusRequest
 *
 * Validates input for the publication status transition endpoint.
 * Authorization is delegated to PublicationPolicy::update() (SDD
 * §3.6.4) — the exact next-status check itself is enforced by
 * PublicationService::transition().
 *
 * SDD Reference: RGMS SDD §3.6.2, §3.6.4
 */
final class TransitionPublicationStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                'In-Preparation', 'Submitted', 'Under-Review', 'Accepted', 'Published', 'Retracted',
            ])],
        ];
    }
}