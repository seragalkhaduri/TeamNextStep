<?php

namespace App\Domain\Students\Enums;

/**
 * Student enrollment status enum (SDD §4.2 — students table).
 */
enum EnrollmentStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case GRADUATED = 'GRADUATED';
    case WITHDRAWN = 'WITHDRAWN';
}
