<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Rules;

use App\Domain\ResearchGroups\Models\ResearchGroup;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * BelongsToAuthScope
 *
 * Verifies that a submitted research_group_id belongs to a research
 * group the authenticated user is authorized to act on: its PI, or
 * research_admin. Assumes the id has already passed
 * exists:research_groups,id (SDD §3.3.12) — this rule only checks
 * authorization scope, not existence.
 *
 * SDD Reference: RGMS SDD §3.3.5, §3.3.12
 */
final class BelongsToAuthScope implements ValidationRule
{
    /**
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        if ($user === null) {
            $fail('Unauthenticated.');

            return;
        }

        if (in_array('research_admin', $user->roles ?? [], true)) {
            return;
        }

        $group = ResearchGroup::query()->find($value);

        if ($group === null || $group->pi_staff_id !== $user->id) {
            $fail('The :attribute does not belong to a research group you are authorized to act on.');
        }
    }
}