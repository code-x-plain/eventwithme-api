<?php

namespace App\Controller\Api;

use App\Api\Exception\ApiException;
use App\Api\Response\ApiResponse;
use App\Api\Response\ApiResponseFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiController extends AbstractController
{
    protected SerializerInterface $serializer;
    protected ValidatorInterface $validator;
    protected ApiResponseFormatter $responseFormatter;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ApiResponseFormatter $responseFormatter
    ) {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->responseFormatter = $responseFormatter;
    }
    
    /**
     * Create a standard success response
     */
    protected function successResponse($data = null, ?string $message = null, int $status = Response::HTTP_OK, array $context = []): JsonResponse
    {
        // Get current request
        $request = $this->getCurrentRequest();
        
        // Format data if it's not null and formatter is available
        if ($data !== null) {
            $data = $this->responseFormatter->formatResponse($request, $data, $context);
        }
        
        return ApiResponse::success($data, $message, $status, [], $context);
    }
    
    /**
     * Create a standard error response
     */
    protected function errorResponse(string $message, $errors = null, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return ApiResponse::error($message, $errors, $status);
    }
    
    /**
     * Create a response with pagination metadata
     */
    protected function paginatedResponse($data, int $total, int $page, int $limit, array $groups = ['user:read']): JsonResponse
    {
        // Get current request
        $request = $this->getCurrentRequest();
        
        // Format data if formatter is available
        $formattedData = $this->responseFormatter->formatResponse($request, $data, ['groups' => $groups]);
        
        $meta = [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
        ];
        
        return ApiResponse::withMeta($formattedData, $meta, 200, [], ['groups' => $groups]);
    }
    
    /**
     * Create a cacheable response
     */
    protected function cacheableResponse($data, int $maxAge = 60, array $context = []): JsonResponse
    {
        $request = $this->getCurrentRequest();
        
        // Format data if it's not null
        if ($data !== null) {
            $data = $this->responseFormatter->formatResponse($request, $data, $context);
        }
        
        $response = ApiResponse::success($data);
        
        // Add cache headers
        $response->setMaxAge($maxAge);
        $response->setPublic();
        
        return $response;
    }
    
    /**
     * Get current request object
     */
    protected function getCurrentRequest(): Request
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }
    
    /**
     * Get the API version from the current request
     */
    protected function getApiVersion(): string
    {
        $request = $this->getCurrentRequest();
        return $request->attributes->get('api_version', '1.0');
    }
    
    /**
     * Validate and deserialize request data
     * 
     * @param Request $request The HTTP request
     * @param string $type The class to deserialize to
     * @param array $validationGroups Optional validation groups
     * @return array [object|null, array|null] The deserialized object or validation errors
     */
    protected function validateRequest(Request $request, string $type, array $validationGroups = ['Default']): array
    {
        $content = $request->getContent();
        if (empty($content)) {
            return [null, ['Request body cannot be empty']];
        }
        
        try {
            $object = $this->serializer->deserialize($content, $type, 'json');
        } catch (\Exception $e) {
            return [null, ['Invalid JSON format: ' . $e->getMessage()]];
        }
        
        $violations = $this->validator->validate($object, null, $validationGroups);
        
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return [null, $errors];
        }
        
        return [$object, null];
    }

    /**
     * Validate a deserialized DTO object
     * 
     * @throws ApiException if validation fails
     */
    protected function validateRequestDTO(object $dto, array $validationGroups = ['Default']): void
    {
        $violations = $this->validator->validate($dto, null, $validationGroups);
        
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            
            throw new ApiException('Validation failed', $errors, Response::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Deserialize request content to a DTO and validate it
     * 
     * @throws ApiException if deserialization or validation fails
     */
    protected function deserializeAndValidate(Request $request, string $dtoClass, array $validationGroups = ['Default']): object
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                $dtoClass,
                'json'
            );
            
            $this->validateRequestDTO($dto, $validationGroups);
            
            return $dto;
        } catch (\Symfony\Component\Serializer\Exception\NotEncodableValueException $e) {
            throw new ApiException('Invalid JSON format: ' . $e->getMessage(), [], Response::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Handle exceptions and return appropriate error response
     */
    protected function handleException(\Throwable $e): JsonResponse
    {
        if ($e instanceof \JsonException) {
            return $this->errorResponse('Invalid JSON format', null, Response::HTTP_BAD_REQUEST);
        }
        
        // Handle specific exceptions from namespace App\Api\Exception\Auth
        if (str_starts_with(get_class($e), 'App\\Api\\Exception\\Auth\\')) {
            $errors = [];
            if ($e instanceof ApiException) {
                $errors = $e->getErrors();
            }
            $statusCode = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            return $this->errorResponse($e->getMessage(), $errors, $statusCode);
        }
        
        if ($e instanceof ApiException) {
            return $this->errorResponse($e->getMessage(), $e->getErrors(), $e->getStatusCode());
        }
        
        // Default error handling for any other exceptions
        $context = $_ENV['APP_ENV'] === 'dev' ? ['error' => $e->getMessage()] : [];
        $statusCode = $e instanceof \RuntimeException ? Response::HTTP_BAD_REQUEST : Response::HTTP_INTERNAL_SERVER_ERROR;
        
        return $this->errorResponse('An error occurred', $context, $statusCode);
    }
} 