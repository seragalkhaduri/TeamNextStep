<?php

namespace App\Domain\Auth\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidCredentialsException extends HttpException
{
    public function __construct()
    {
        parent::__construct(401, 'Invalid username or password.');
    }
}
