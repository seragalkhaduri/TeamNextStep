<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exceptions;

use RuntimeException;

/**
 * IneligiblePIException
 *
 * Thrown when a staff member does not meet the eligibility criteria
 * to serve as Principal Investigator on a research group.
 *
 * Mapped by app/Exceptions/Handler.php to HTTP 422:
 * { error: 'ineligible_pi', message: '...' }
 *
 * SDD Reference: RGMS SDD §3.1.7, §3.14.8
 */
final class IneligiblePIException extends RuntimeException
{
}