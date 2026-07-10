<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Rules;

use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ValidUimpLaboratory
 *
 * Verifies that a laboratory_ref_id exists in UIMP, via
 * UimpMasterDataClient::validateLaboratory() (SDD §3.8.5).
 *
 * SDD Reference: RGMS SDD §3.8.5
 */
final class ValidUimpLaboratory implements ValidationRule
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
            $fail('The :attribute must be a valid UIMP laboratory identifier.');

            return;
        }

        if (! $this->uimpClient->validateLaboratory($value)) {
            $fail('The :attribute does not correspond to a known UIMP laboratory.');
        }
    }
}