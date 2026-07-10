<?php

namespace App\Domain\Students\Enums;

/**
 * Gender enum (SDD §4.2 — students table).
 */
enum Gender: string
{
    case MALE = 'MALE';
    case FEMALE = 'FEMALE';
}
