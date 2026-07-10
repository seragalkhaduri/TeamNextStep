<?php

namespace App\Domain\Auth\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidResetTokenException extends HttpException
{
    public function __construct()
    {
        parent::__construct(400, 'Invalid or expired password reset token.');
    }
}
