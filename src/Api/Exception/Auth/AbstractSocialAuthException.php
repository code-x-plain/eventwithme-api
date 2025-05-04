<?php

namespace App\Api\Exception\Auth;

use App\Api\Exception\ApiException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base exception for social authentication errors
 */
class AbstractSocialAuthException extends ApiException
{
    public function __construct(
        string $message = 'Social authentication error',
        array $errors = [],
        int $statusCode = Response::HTTP_UNAUTHORIZED,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errors, $statusCode, $previous);
    }
}
