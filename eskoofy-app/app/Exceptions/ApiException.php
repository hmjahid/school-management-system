<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        protected int $statusCode = 400,
        protected ?string $errorCode = null,
        protected ?array $errors = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }
}
