<?php

namespace App\Domain\Auth\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidRefreshTokenException extends HttpException
{
    public function __construct()
    {
        parent::__construct(401, 'Invalid or expired refresh token.');
    }
}
