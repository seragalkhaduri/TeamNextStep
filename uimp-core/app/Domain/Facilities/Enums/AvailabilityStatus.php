<?php

namespace App\Domain\Facilities\Enums;

/**
 * Room availability status enum (SDD §4.2 — rooms table).
 */
enum AvailabilityStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case OCCUPIED = 'OCCUPIED';
    case UNDER_MAINTENANCE = 'UNDER_MAINTENANCE';
}
