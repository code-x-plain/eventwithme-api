<?php

namespace App\Controller\Api;

use App\Api\Response\ApiResponseFormatter;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[Route('/api', name: 'auth_')]
#[OA\Tag(name: 'Authentication')]
class AuthController extends AbstractApiController
{
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ApiResponseFormatter $responseFormatter,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($serializer, $validator, $responseFormatter);
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                    new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Doe')
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
                            new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                            new OA\Property(property: 'lastName', type: 'string', example: 'Doe')
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
            $data = json_decode($request->getContent(), true);

            if (empty($data['email']) || empty($data['password']) || empty($data['firstName']) || empty($data['lastName'])) {
                return $this->errorResponse('Missing required fields');
            }

            // Check if user already exists
            $existingUser = $this->userRepository->findOneByEmail($data['email']);
            if ($existingUser) {
                return $this->errorResponse('User already exists with this email', null, Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);

            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $data['password']
            );
            $user->setPassword($hashedPassword);

            // Validate user data
            $violations = $this->validator->validate($user);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }

                return $this->errorResponse('Validation failed', $errors);
            }

            // Save the user
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->successResponse(
                [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                ],
                'User registered successfully',
                Response::HTTP_CREATED
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'An error occurred during registration',
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        summary: 'Login to get JWT token',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'user@example.com'),
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
        // This route is used by the security system to generate a JWT token
        // The actual authentication is handled by the security.yaml configuration
        // and LexikJWTAuthenticationBundle

        // This method is never executed because the JWT bundle handles the response
        return $this->successResponse(null, 'Login endpoint');
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    #[OA\Get(
        path: '/api/profile',
        summary: 'Get user profile',
        tags: ['Authentication'],
        security: [['Bearer' => []]],
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
                            new OA\Property(property: 'firstName', type: 'string', example: 'John'),
                            new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
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
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->errorResponse('User not authenticated', null, Response::HTTP_UNAUTHORIZED);
        }

        return $this->successResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }
}
