<?php

namespace App\Domain\Employees\Enums;

/**
 * Academic rank enum (SDD §4.2 — employees table).
 * Nullable — only applies when staff_type = ACADEMIC.
 */
enum AcademicRank: string
{
    case LECTURER = 'LECTURER';
    case ASSISTANT_PROFESSOR = 'ASSISTANT_PROFESSOR';
    case ASSOCIATE_PROFESSOR = 'ASSOCIATE_PROFESSOR';
    case PROFESSOR = 'PROFESSOR';
    case EMERITUS = 'EMERITUS';
}
