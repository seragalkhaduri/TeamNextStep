<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services\Clients\Dto;

/**
 * UimpDepartmentDto
 *
 * Immutable DTO representing a UIMP Department record. Never
 * persisted — RGMS does not maintain its own copy of department data
 * (master architectural constraint: no direct UIMP database access).
 *
 * SDD Reference: RGMS SDD §3.14.5
 */
final readonly class UimpDepartmentDto
{
    public function __construct(
        public string $id,
        public string $nameEn,
        public string $nameAr,
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
}