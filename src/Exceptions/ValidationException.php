<?php
/**
 * Validation Exception
 */

namespace App\Exceptions;

use App\Core\Validator;

class ValidationException extends \RuntimeException
{
    private array $errors;
    protected $code = 422;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ];
    }
}

