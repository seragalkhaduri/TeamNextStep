<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exceptions;

use RuntimeException;
use Throwable;

/**
 * UimpApiException
 *
 * Thrown by any UIMP integration client (UimpMasterDataClient,
 * UimpNotificationClient, UimpAuditClient) when the UIMP API Gateway
 * is unreachable, times out, or returns a server error.
 *
 * Mapped by app/Exceptions/Handler.php to HTTP 502:
 * { error: 'uimp_unavailable', retry_after: 30 }
 *
 * SDD Reference: RGMS SDD §3.14.5, §3.14.8
 */
final class UimpApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfter = 30,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}