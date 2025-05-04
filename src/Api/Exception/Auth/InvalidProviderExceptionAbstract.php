<?php

namespace App\Api\Exception\Auth;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when an invalid social provider is specified
 */
class InvalidProviderExceptionAbstract extends AbstractSocialAuthException
{
    public function __construct(
        string $provider,
        array $supportedProviders = ['google', 'facebook', 'apple'],
        ?\Throwable $previous = null
    ) {
        $errors = [
            'provider' => $provider,
            'supported_providers' => $supportedProviders,
            'error' => 'The specified provider is not supported'
        ];

        parent::__construct(
            'Invalid provider: ' . $provider . '. Supported providers: ' . implode(', ', $supportedProviders),
            $errors,
            Response::HTTP_BAD_REQUEST,
            $previous
        );
    }
}
