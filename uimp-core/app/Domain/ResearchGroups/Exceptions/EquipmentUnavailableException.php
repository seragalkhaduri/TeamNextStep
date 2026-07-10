<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exceptions;

use RuntimeException;
use Throwable;

/**
 * EquipmentUnavailableException
 *
 * Thrown when a booking is requested for equipment that is not
 * currently Available (e.g. Under-Maintenance, Decommissioned,
 * In-Transit).
 *
 * Mapped by app/Exceptions/Handler.php to HTTP 409:
 * { error: 'equipment_unavailable', status: '...' }
 *
 * SDD Reference: RGMS SDD §3.9, §3.14.8
 */
final class EquipmentUnavailableException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $status,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}