<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Rules;

use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ValidUimpStaffId
 *
 * Verifies that a given UUID corresponds to an existing UIMP
 * Academic Staff record, via UimpMasterDataClient::getAcademicStaff().
 * Used on pi_staff_id fields — validates existence only; PI
 * eligibility (isEligibleForPI()) is enforced separately in
 * ResearchGroupService::create() (SDD §3.1.7), not here, to keep
 * this rule a pure input-validation concern.
 *
 * SDD Reference: RGMS SDD §3.1.12
 */
final class ValidUimpStaffId implements ValidationRule
{
    public function __construct(
        private readonly UimpMasterDataClient $uimpClient = new UimpMasterDataClient(),
    ) {
    }

    /**
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a valid UIMP staff identifier.');

            return;
        }

        $staff = $this->uimpClient->getAcademicStaff($value);

        if ($staff === null) {
            $fail('The :attribute does not correspond to a known UIMP academic staff member.');
        }
    }
}