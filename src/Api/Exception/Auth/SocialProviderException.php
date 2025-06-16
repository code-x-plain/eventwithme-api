<?php

namespace App\Api\Exception\Auth;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when there's an error with a social provider
 */
class SocialProviderException extends AbstractSocialAuthException
{
    public function __construct(
        string $provider,
        string $errorDetails,
        ?\Throwable $previous = null
    ) {
        $errors = [
            'provider' => $provider,
            'error' => $errorDetails
        ];

        parent::__construct(
            'Error with ' . $provider . ' provider: ' . $errorDetails,
            $errors,
            Response::HTTP_SERVICE_UNAVAILABLE,
            $previous
        );
    }
}
