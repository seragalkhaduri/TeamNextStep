<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exceptions;

use RuntimeException;
use Throwable;

/**
 * BookingConflictException
 *
 * Thrown when a requested equipment booking time window overlaps an
 * existing Confirmed booking for the same equipment (SDD §4.6
 * conflict-detection index).
 *
 * Mapped by app/Exceptions/Handler.php to HTTP 409:
 * { error: 'booking_conflict', conflicts: [...] }
 *
 * SDD Reference: RGMS SDD §3.9, §4.6, §3.14.8
 */
final class BookingConflictException extends RuntimeException
{
    /**
     * @param list<array<string, mixed>> $conflicts
     */
    public function __construct(
        string $message,
        public readonly array $conflicts = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}