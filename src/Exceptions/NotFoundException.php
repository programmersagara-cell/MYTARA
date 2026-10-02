<?php
/**
 * Not Found Exception (404)
 */

namespace App\Exceptions;

class NotFoundException extends \RuntimeException
{
    protected $code = 404;

    public function __construct(string $message = 'Resource not found', int $code = 404, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

