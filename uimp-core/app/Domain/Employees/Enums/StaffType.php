<?php

namespace App\Domain\Employees\Enums;

/**
 * Staff type enum — single-table discriminator (SDD §3.2.2, §7.2 trade-off 4).
 */
enum StaffType: string
{
    case ACADEMIC = 'ACADEMIC';
    case NON_ACADEMIC = 'NON_ACADEMIC';
}
