<?php

namespace App\Api\Exception\Auth;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when email is missing from a social profile
 */
class MissingEmailExceptionAbstract extends AbstractSocialAuthException
{
    public function __construct(
        string $provider,
        ?\Throwable $previous = null
    ) {
        $errors = [
            'provider' => $provider,
            'error' => 'Email is required for authentication'
        ];

        parent::__construct(
            'Email is required for ' . $provider . ' authentication',
            $errors,
            Response::HTTP_BAD_REQUEST,
            $previous
        );
    }
}
