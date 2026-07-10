<?php

declare(strict_types=1);

namespace App\Http\Requests\Rgms;

use App\Domain\ResearchGroups\Rules\ValidGroupMember;
use Illuminate\Foundation\Http\FormRequest;

/**
 * StorePatentRequest
 *
 * Validates input for POST /api/v1/research-groups/{gid}/patents.
 * Authorization is delegated to PatentPolicy::create() (SDD §3.7.4).
 *
 * status is intentionally excluded — PatentService::register() always
 * creates a patent with status Filed (SDD §3.7.6).
 *
 * SDD Reference: RGMS SDD §3.7.4, §3.7.7
 */
final class StorePatentRequest extends FormRequest
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

        return [
            'title' => ['required', 'string', 'max:500'],
            'registration_authority' => ['required', 'string', 'max:200'],
            'filing_date' => ['required', 'date', 'date_format:Y-m-d'],
            'inventor_uimp_ids' => ['required', 'array', 'min:1'],
            'inventor_uimp_ids.*' => ['string', new ValidGroupMember($groupId)],
        ];
    }
}