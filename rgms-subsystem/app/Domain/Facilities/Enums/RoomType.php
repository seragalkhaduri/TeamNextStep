<?php

namespace App\Domain\Facilities\Enums;

/**
 * Room type enum (SDD §4.2 — rooms table).
 */
enum RoomType: string
{
    case LECTURE_HALL = 'LECTURE_HALL';
    case LAB = 'LAB';
    case OFFICE = 'OFFICE';
    case LIBRARY = 'LIBRARY';
    case CAFETERIA = 'CAFETERIA';
    case OTHER = 'OTHER';
}
