<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exceptions;

use RuntimeException;
use Throwable;

/**
 * DuplicateResearchGroupException
 *
 * Thrown when BR-007 is violated: an Active research group already
 * exists with the same PI, Research Field, and Research Area within
 * the current fiscal year.
 *
 * Mapped by app/Exceptions/Handler.php to HTTP 409:
 * { error: 'duplicate_group', existing_id: '...' }
 *
 * SDD Reference: RGMS SDD §3.1.2 (BR-007), §3.14.8
 */
final class DuplicateResearchGroupException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $existingId,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}