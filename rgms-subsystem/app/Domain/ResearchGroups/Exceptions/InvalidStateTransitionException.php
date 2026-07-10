<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exceptions;

use RuntimeException;
use Throwable;

/**
 * InvalidStateTransitionException
 *
 * Thrown when a requested lifecycle status transition is not allowed
 * by the governing state machine, or is missing a required
 * justification (e.g. transitioning a ResearchGroup to Suspended or
 * Archived without one).
 *
 * Mapped by app/Exceptions/Handler.php to HTTP 422:
 * { error: 'invalid_transition', from: '...', to: '...' }
 *
 * SDD Reference: RGMS SDD §3.1.7, §3.14.8
 */
final class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}