<?php

namespace App\Domain\Auth\Enums;

/**
 * Preferred language enum (SDD §4.5, DB-004).
 * Default: Arabic (ar).
 */
enum PreferredLanguage: string
{
    case AR = 'ar';
    case EN = 'en';
}
