<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Rules;

use App\Domain\ResearchGroups\Models\GroupMembership;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ValidGroupMember
 *
 * Verifies that a given UIMP member ID is an active member of a
 * specific research group. Applied per-element to arrays like
 * author_uimp_ids / inventor_uimp_ids (SDD §3.6.6).
 *
 * SDD Reference: RGMS SDD §3.6.5, §3.6.6
 */
final class ValidGroupMember implements ValidationRule
{
    public function __construct(
        private readonly string $groupId,
    ) {
    }

    /**
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Each :attribute entry must be a valid UIMP member identifier.');

            return;
        }

        $isActiveMember = GroupMembership::query()
            ->where('group_id', $this->groupId)
            ->where('member_uimp_id', $value)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->exists();

        if (! $isActiveMember) {
            $fail('Each :attribute entry must be an active member of the research group.');
        }
    }
}