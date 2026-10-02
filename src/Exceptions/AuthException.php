<?php
/**
 * Authentication Exception
 */

namespace App\Exceptions;

class AuthException extends \RuntimeException
{
    protected $code = 401;

    public function __construct(string $message = 'Authentication failed', int $code = 401, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

