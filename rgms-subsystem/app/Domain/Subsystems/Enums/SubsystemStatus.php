<?php

namespace App\Domain\Subsystems\Enums;

/**
 * Subsystem status enum (SDD §4.2 — subsystems table).
 */
enum SubsystemStatus: string
{
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case SUSPENDED = 'SUSPENDED';
}
