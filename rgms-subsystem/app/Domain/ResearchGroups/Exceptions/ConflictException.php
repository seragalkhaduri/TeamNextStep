<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exceptions;

use RuntimeException;

/**
 * ConflictException
 *
 * Generic domain exception for state conflicts not covered by a more
 * specific exception class (e.g. DuplicateResearchGroupException,
 * BookingConflictException).
 *
 * Mapped by app/Exceptions/Handler.php to HTTP 409:
 * { error: 'conflict', message: '...' }
 *
 * SDD Reference: RGMS SDD §3.14.8
 */
final class ConflictException extends RuntimeException
{
}