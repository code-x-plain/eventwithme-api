<?php

namespace App\Controller\Api\Auth;

use App\Api\Exception\Auth\InvalidProviderExceptionAbstract;
use App\Api\Request\Auth\SocialAuthRequest;
use App\Api\Request\Auth\SocialConnectRequest;
use App\Api\Response\ApiResponseFormatter;
use App\Api\Response\Auth\SocialConnectResponse;
use App\Controller\Api\AbstractApiController;
use App\Service\SocialAuthService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'auth_')]
#[OA\Tag(name: 'Social Authentication')]
class SocialAuthController extends AbstractApiController
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

    #[Route('/social', name: 'social', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/social',
        summary: 'Authenticate with a social token (for mobile apps)',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['provider', 'token'],
                properties: [
                    new OA\Property(property: 'provider', type: 'string', enum: ['google', 'facebook', 'apple']),
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'userData', required: ['no'], properties: [
                        new OA\Property(property: 'name', properties: [
                            new OA\Property(property: 'firstName', type: 'string'),
                            new OA\Property(property: 'lastName', type: 'string')
                        ], type: 'object')
                    ], type: 'object')
                ]
            )
        ),
        tags: ['Social Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns JWT token after successful authentication',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'token', type: 'string'),
                            new OA\Property(property: 'user', properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'firstName', type: 'string'),
                                new OA\Property(property: 'lastName', type: 'string'),
                                new OA\Property(property: 'avatarUrl', type: 'string', nullable: true)
                            ], type: 'object')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid request'),
            new OA\Response(response: 401, description: 'Authentication failed')
        ]
    )]
    public function authenticateWithSocialToken(Request $request): JsonResponse
    {
        try {
            $socialAuthRequest = $this->deserializeAndValidate($request, SocialAuthRequest::class);

            $socialAuthResponse = match($socialAuthRequest->getProvider()) {
                'google' => $this->socialAuthService->authenticateWithGoogleToken($socialAuthRequest->getToken()),
                'facebook' => $this->socialAuthService->authenticateWithFacebookToken($socialAuthRequest->getToken()),
                'apple' => $this->socialAuthService->authenticateWithAppleToken($socialAuthRequest->getToken(), $socialAuthRequest->getUserData()),
                default => throw new InvalidProviderExceptionAbstract($socialAuthRequest->getProvider())
            };

            return $this->successResponse($socialAuthResponse);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[Route('/social/connect', name: 'social_connect', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/social/connect',
        summary: 'Get OAuth authorization URL for the specified provider',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['provider'],
                properties: [
                    new OA\Property(property: 'provider', type: 'string', enum: ['google', 'facebook', 'apple'])
                ]
            )
        ),
        tags: ['Social Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns the redirect URL for OAuth authorization',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'redirectUrl', type: 'string')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid or missing provider',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', properties: [
                            new OA\Property(property: 'message', type: 'string')
                        ], type: 'object')
                    ]
                )
            )
        ]
    )]
    public function connect(Request $request): JsonResponse
    {
        try {
            $socialConnectRequest = $this->deserializeAndValidate($request, SocialConnectRequest::class);

            $redirectUrl = match($socialConnectRequest->getProvider()) {
                'google' => $this->clientRegistry->getClient('google')->getOAuth2Provider()->getAuthorizationUrl(),
                'facebook' => $this->clientRegistry->getClient('facebook')->getOAuth2Provider()->getAuthorizationUrl(),
                'apple' => $this->clientRegistry->getClient('apple')->getOAuth2Provider()->getAuthorizationUrl(),
                default => throw new InvalidProviderExceptionAbstract($socialConnectRequest->getProvider())
            };

            return $this->cacheableResponse((new SocialConnectResponse())->setRedirectUrl($redirectUrl), 300); // Cache for 5 minutes
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
