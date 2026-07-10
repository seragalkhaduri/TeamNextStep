<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services\Clients\Dto;

/**
 * UimpAcademicStaffDto
 *
 * Immutable DTO representing a UIMP Academic Staff record. Never
 * persisted — RGMS does not maintain its own copy of staff data
 * (master architectural constraint: no direct UIMP database access).
 *
 * SDD Reference: RGMS SDD §3.14.5, §3.1.7, §3.1.11
 */
final readonly class UimpAcademicStaffDto
{
    public function __construct(
        public string $id,
        public string $nameEn,
        public string $nameAr,
        public string $academicRank,
        public bool $isEligibleForPi,
    ) {
    }

    /**
     * Whether this staff member is eligible to serve as Principal
     * Investigator on a research group (SDD §3.1.7, FR-MEM-003).
     * Eligibility itself is computed by UimpMasterDataClient at
     * construction time.
     */
    public function isEligibleForPI(): bool
    {
        return $this->isEligibleForPi;
    }

    /**
     * Resolve a locale-aware display name. Defaults to English;
     * pass 'ar' for the Arabic name (SDD §4.5 dual-column convention,
     * mirrored from UIMP).
     */
    public function displayName(string $locale = 'en'): string
    {
        return $locale === 'ar' ? $this->nameAr : $this->nameEn;
    }
}