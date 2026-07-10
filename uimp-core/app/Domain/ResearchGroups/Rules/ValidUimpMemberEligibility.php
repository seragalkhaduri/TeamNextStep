<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Rules;

use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ValidUimpMemberEligibility
 *
 * Verifies that a member_uimp_id (a) exists in UIMP as either Staff
 * or a Student, and (b) their institutional type matches the
 * requested research role, via
 * UimpMasterDataClient::validateMemberEligibility().
 *
 * Constructed with the target role (e.g. new
 * ValidUimpMemberEligibility($request->input('role'))) since
 * eligibility depends on the role being requested, not on
 * member_uimp_id alone.
 *
 * SDD Reference: RGMS SDD §3.2.5, §3.2.12
 */
final class ValidUimpMemberEligibility implements ValidationRule
{
    public function __construct(
        private readonly ?string $role,
        private readonly UimpMasterDataClient $uimpClient = new UimpMasterDataClient(),
    ) {
    }

    /**
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a valid UIMP member identifier.');

            return;
        }

        if ($this->role === null || $this->role === '') {
            $fail('A role must be specified to validate member eligibility.');

            return;
        }

        $staff = $this->uimpClient->getAcademicStaff($value);
        $memberType = $staff !== null ? 'Staff' : 'Student';

        $isEligible = $this->uimpClient->validateMemberEligibility($value, $memberType, $this->role);

        if (! $isEligible) {
            $fail('The :attribute is not eligible for the requested role.');
        }
    }
}