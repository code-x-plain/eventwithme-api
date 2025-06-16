<?php

namespace App\Controller\Api;

use App\Api\Response\ApiResponseFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ApiResponseFormatter $responseFormatter
    ) {
        parent::__construct($serializer, $validator, $responseFormatter);
    }

    /**
     * API information endpoint
     *
     * Returns general information about the API, including version and available endpoints.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $version = $this->getApiVersion();

        $apiInfo = [
            'name' => 'EventWithMe API',
            'version' => $version,
            'version_status' => $request->attributes->get('api_version_status', 'current'),
            'endpoints' => [
                'auth' => [
                    'register' => '/api/auth/register',
                    'login' => '/api/auth/login',
                    'profile' => '/api/auth/profile',
                ],
            ],
            'documentation' => '/api/doc',
        ];

        // Add version-specific information
        if ($version === '1.1') {
            // Add version 1.1 specific endpoints if needed
        }

        // Add warning for deprecated versions
        if ($request->attributes->has('api_version_deprecated')) {
            $apiInfo['deprecated'] = true;

            if ($request->attributes->has('api_version_sunset')) {
                $apiInfo['sunset_date'] = $request->attributes->get('api_version_sunset');
                $apiInfo['warning'] = 'This API version is deprecated and will be removed after ' . $apiInfo['sunset_date'];
            } else {
                $apiInfo['warning'] = 'This API version is deprecated. Please migrate to a newer version.';
            }
        }

        $response = $this->successResponse($apiInfo, 'Welcome to the EventWithMe API');

        // Add sunset header for deprecated versions
        if ($request->attributes->has('api_version_sunset')) {
            $response->headers->set('Sunset', date(\DateTime::RFC7231, strtotime($request->attributes->get('api_version_sunset'))));
        }

        return $response;
    }

    /**
     * API health check endpoint
     *
     * Used for monitoring the API health and status.
     */
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'healthy',
            'environment' => $_ENV['APP_ENV'],
            'timestamp' => (new \DateTime())->format('c')
        ]);
    }

    /**
     * API versioning examples endpoint
     *
     * Shows how to access the API with different versions.
     */
    #[Route('/version-examples', name: 'version_examples', methods: ['GET'])]
    public function versionExamples(): JsonResponse
    {
        $examples = [
            'url_based' => [
                'description' => 'Access a specific API version using URL path',
                'examples' => [
                    'v1.0' => '/api/v1.0/auth/profile',
                    'v1.1' => '/api/v1.1/auth/profile'
                ]
            ],
            'header_based' => [
                'description' => 'Access a specific API version using HTTP headers',
                'examples' => [
                    'using_accept' => 'Accept: application/json; version=1.1',
                    'using_custom_header' => 'X-API-Version: 1.1'
                ]
            ],
            'available_versions' => [
                '1.0' => ['status' => 'current'],
                '1.1' => ['status' => 'beta'],
                '0.9' => ['status' => 'deprecated', 'sunset_date' => '2024-12-31'],
            ]
        ];

        return $this->successResponse($examples, 'API versioning examples');
    }
}
