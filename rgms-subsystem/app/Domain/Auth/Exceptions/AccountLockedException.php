<?php

namespace App\Domain\Auth\Exceptions;

use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountLockedException extends HttpException
{
    public function __construct(?Carbon $lockedUntil = null)
    {
        $message = 'Account is locked due to too many failed login attempts.';
        if ($lockedUntil) {
            $message .= ' Try again after ' . $lockedUntil->toIso8601String() . '.';
        }

        parent::__construct(423, $message);
    }
}
