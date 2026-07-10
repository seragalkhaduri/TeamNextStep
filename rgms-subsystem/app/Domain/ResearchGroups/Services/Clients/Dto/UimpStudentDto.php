<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services\Clients\Dto;

/**
 * UimpStudentDto
 *
 * Immutable DTO representing a UIMP Student record. Never persisted —
 * RGMS does not maintain its own copy of student data (master
 * architectural constraint: no direct UIMP database access).
 *
 * SDD Reference: RGMS SDD §3.14.5, §3.2.7
 */
final readonly class UimpStudentDto
{
    public function __construct(
        public string $id,
        public string $nameEn,
        public string $nameAr,
        public string $enrollmentStatus,
    ) {
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

    /**
     * Whether this student is currently active — required for
     * Graduate-Researcher membership eligibility (SDD §3.2.7).
     */
    public function isActive(): bool
    {
        return $this->enrollmentStatus === 'ACTIVE';
    }
}