<?php

namespace App\Api\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    private array $errors;

    public function __construct(
        string $message = '', 
        array $errors = [], 
        int $statusCode = 400, 
        ?\Throwable $previous = null, 
        array $headers = [], 
        int $code = 0
    ) {
        $this->errors = $errors;
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
} 