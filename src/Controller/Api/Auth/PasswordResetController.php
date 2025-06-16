<?php

namespace App\Controller\Api\Auth;

use App\Api\Exception\Auth\PasswordResetException;
use App\Api\Request\Auth\RequestPasswordResetRequest;
use App\Api\Request\Auth\ResetPasswordRequest;
use App\Api\Response\ApiResponseFormatter;
use App\Api\Response\Auth\PasswordResetResponse;
use App\Controller\Api\AbstractApiController;
use App\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth/password', name: 'auth_password_')]
#[OA\Tag(name: 'Authentication')]
class PasswordResetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ApiResponseFormatter $responseFormatter,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct($serializer, $validator, $responseFormatter);
    }

    #[Route('/request-reset', name: 'request_reset', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/password/request-reset',
        summary: 'Request a password reset',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'user@example.com')
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset email sent',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Password reset instructions sent to your email')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation failed'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function requestReset(Request $request): JsonResponse
    {
        try {
            $resetRequest = $this->deserializeAndValidate($request, RequestPasswordResetRequest::class);

            $user = $this->userRepository->findOneByEmail($resetRequest->getEmail());
            if (!$user) {
                return $this->successResponse(
                    PasswordResetResponse::success('Password reset token generated. In a real application, an email would be sent.')
                );
            }

            // Generate a unique reset token
            $token = bin2hex(random_bytes(32));

            // Store the token with the user
            $this->userRepository->setPasswordResetToken($user, $token);

            // In a real application, you would send an email here with the reset link
            // For example: https://your-frontend-app.com/reset-password?token=$token

            return $this->successResponse(
                PasswordResetResponse::success('Password reset token generated. In a real application, an email would be sent.')
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[Route('/reset', name: 'reset', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/password/reset',
        summary: 'Reset password using token',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'reset-token-here'),
                    new OA\Property(property: 'password', type: 'string', example: 'newPassword123')
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Password has been reset successfully')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid or expired token'),
            new OA\Response(response: 404, description: 'Token not found')
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $resetRequest = $this->deserializeAndValidate($request, ResetPasswordRequest::class);

            // Find user by reset token
            $user = $this->userRepository->findOneByResetToken($resetRequest->getToken());
            if (!$user) {
                throw PasswordResetException::invalidToken();
            }

            // Check if token is expired
            if (!$user->isResetTokenValid()) {
                throw PasswordResetException::tokenExpired();
            }

            // Reset the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $resetRequest->getPassword()
            );
            $user->setPassword($hashedPassword);

            // Clear the reset token
            $this->userRepository->clearPasswordResetToken($user);

            return $this->successResponse(
                PasswordResetResponse::success('Password has been reset successfully')
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[Route('/validate-token/{token}', name: 'validate_token', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/password/validate-token/{token}',
        summary: 'Validate a password reset token',
        tags: ['Authentication'],
        parameters: [
            new OA\Parameter(
                name: 'token',
                description: 'Password reset token',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token is valid',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Token is valid')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid or expired token')
        ]
    )]
    public function validateToken(string $token): JsonResponse
    {
        try {
            // Find user by reset token
            $user = $this->userRepository->findOneByResetToken($token);
            if (!$user) {
                throw PasswordResetException::invalidToken();
            }

            // Check if token is expired
            if (!$user->isResetTokenValid()) {
                throw PasswordResetException::tokenExpired();
            }

            return $this->successResponse(
                PasswordResetResponse::success('Token is valid')
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
