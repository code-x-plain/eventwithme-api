<?php

namespace App\Controller\Api\Auth;

use App\Api\Exception\ApiException;
use App\Api\Request\Auth\RegisterRequest;
use App\Api\Response\ApiResponseFormatter;
use App\Api\Response\Auth\UserResponse;
use App\Controller\Api\AbstractApiController;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'auth_')]
#[OA\Tag(name: 'Authentication')]
class AuthController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ApiResponseFormatter $responseFormatter,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct($serializer, $validator, $responseFormatter);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                    new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                    new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'phoneNumber', type: 'string', example: '+901234567890')
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'User registered successfully'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                            new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                            new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                            new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                            new OA\Property(property: 'phoneNumber', type: 'string', example: '+901234567890'),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'])
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation failed'),
            new OA\Response(response: 409, description: 'User already exists')
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        try {
            $registerRequest = $this->deserializeAndValidate($request, RegisterRequest::class);

            $existingUser = $this->userRepository->findOneByEmail($registerRequest->getEmail());
            if ($existingUser) {
                throw new ApiException('User already exists with this email', [], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($registerRequest->getEmail());
            $user->setUsername($registerRequest->getUsername());
            $user->setFirstName($registerRequest->getFirstName());
            $user->setLastName($registerRequest->getLastName());
            $user->setPhoneNumber($registerRequest->getPhoneNumber());

            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $registerRequest->getPassword()
            );
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->successResponse(
                UserResponse::fromEntity($user),
                'User registered successfully',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login to get JWT token',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123')
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'JWT token generated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'refresh_token', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials')
        ]
    )]
    public function login(): JsonResponse
    {
        return $this->successResponse(null, 'Login endpoint');
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/profile',
        summary: 'Get user profile',
        security: [['Bearer' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns user profile',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                            new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                            new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                            new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                            new OA\Property(property: 'phoneNumber', type: 'string', example: '+901234567890'),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'])
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'User not authenticated')
        ]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profile(): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new ApiException('User not authenticated', [], Response::HTTP_UNAUTHORIZED);
            }

            return $this->successResponse(UserResponse::fromEntity($user));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
