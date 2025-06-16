<?php

namespace App\Controller\Api\Auth;

use App\Api\Response\ApiResponseFormatter;
use App\Controller\Api\AbstractApiController;
use App\Service\SocialAuthService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/auth/social', name: 'connect_')]
#[OA\Tag(name: 'Social Authentication')]
class SocialCallbackController extends AbstractApiController
{
    public function __construct(
        SerializerInterface                $serializer,
        ValidatorInterface                 $validator,
        ApiResponseFormatter               $responseFormatter,
        private readonly SocialAuthService $socialAuthService,
        private readonly ClientRegistry    $clientRegistry
    ) {
        parent::__construct($serializer, $validator, $responseFormatter);
    }

    #[Route('/google/check', name: 'google_check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/social/google/check',
        description: 'Handles the OAuth callback from Google and authenticates the user. This is a GET request endpoint.',
        summary: 'Google OAuth callback endpoint'
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns authentication data after successful Google login',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Authentication failed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string')
            ]
        )
    )]
    public function connectGoogleCheck(Request $request): JsonResponse
    {
        return $this->processOAuthCallback('google', $request);
    }

    #[Route('/facebook/check', name: 'facebook_check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/social/facebook/check',
        description: 'Handles the OAuth callback from Facebook and authenticates the user. This is a GET request endpoint.',
        summary: 'Facebook OAuth callback endpoint'
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns authentication data after successful Facebook login',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Authentication failed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string')
            ]
        )
    )]
    public function connectFacebookCheck(Request $request): JsonResponse
    {
        return $this->processOAuthCallback('facebook', $request);
    }

    #[Route('/apple/check', name: 'apple_check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/social/apple/check',
        description: 'Handles the OAuth callback from Apple and authenticates the user. This is a GET request endpoint.',
        summary: 'Apple OAuth callback endpoint'
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns authentication data after successful Apple login',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Authentication failed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string')
            ]
        )
    )]
    public function connectAppleCheck(Request $request): JsonResponse
    {
        return $this->processOAuthCallback('apple', $request);
    }

    private function processOAuthCallback(string $provider, Request $request): JsonResponse
    {
        try {
            $client = $this->clientRegistry->getClient($provider);
            $accessToken = $client->getAccessToken();

            $authData = match($provider) {
                'google' => $this->socialAuthService->authenticateWithGoogleToken($accessToken->getToken()),
                'facebook' => $this->socialAuthService->authenticateWithFacebookToken($accessToken->getToken()),
                'apple' => $this->socialAuthService->authenticateWithAppleToken($accessToken->getToken(), null),
                default => throw new \InvalidArgumentException('Invalid provider')
            };

            return $this->successResponse($authData);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
