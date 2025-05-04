<?php

namespace App\Api\Exception\Auth;

use App\Api\Exception\ApiException;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetException extends ApiException
{
    public function __construct(string $message, array $errors = [], int $statusCode = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct($message, $errors, $statusCode);
    }

    public static function userNotFound(): self
    {
        return new self('User not found with the provided email', [], Response::HTTP_NOT_FOUND);
    }

    public static function invalidToken(): self
    {
        return new self('Invalid or expired password reset token', [], Response::HTTP_BAD_REQUEST);
    }

    public static function tokenExpired(): self
    {
        return new self('Password reset token has expired', [], Response::HTTP_BAD_REQUEST);
    }
} 