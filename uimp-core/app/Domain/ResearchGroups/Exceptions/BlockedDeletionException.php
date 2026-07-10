<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exceptions;

use RuntimeException;
use Throwable;

/**
 * BlockedDeletionException
 *
 * Thrown when a soft-delete operation is blocked because the entity
 * still has active dependencies (e.g. active projects, memberships,
 * financial commitments, or pending equipment bookings).
 *
 * Mapped by app/Exceptions/Handler.php to HTTP 409:
 * { error: 'blocked_deletion', reason: '...', counts: {...} }
 *
 * SDD Reference: RGMS SDD §3.1.7, §4.7 (Soft Delete Strategy —
 * blocking conditions per table), §3.14.8
 */
final class BlockedDeletionException extends RuntimeException
{
    /**
     * @param array<string, int> $counts
     */
    public function __construct(
        string $message,
        public readonly array $counts = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}