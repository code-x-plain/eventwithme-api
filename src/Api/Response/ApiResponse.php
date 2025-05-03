<?php

namespace App\Api\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class ApiResponse extends JsonResponse
{
    /**
     * Create a success response
     *
     * @param mixed|null $data The response data
     * @param string|null $message Optional success message
     * @param int $status HTTP status code
     * @param array $headers Response headers
     * @param array $context Serialization context
     * @return static
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $headers = [], array $context = []): self
    {
        $responseData = [
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $responseData['message'] = $message;
        }

        $response = new self($responseData, $status, $headers);

        if (!empty($context)) {
            $response->setData($responseData);
            $response->setEncodingOptions(self::DEFAULT_ENCODING_OPTIONS | JSON_UNESCAPED_UNICODE);
        }

        return $response;
    }

    /**
     * Create an error response
     *
     * @param string $message Error message
     * @param mixed|null $errors Detailed error information
     * @param int $status HTTP status code
     * @param array $headers Response headers
     * @return static
     */
    public static function error(string $message, mixed $errors = null, int $status = 400, array $headers = []): self
    {
        $responseData = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $responseData['errors'] = $errors;
        }

        return new self($responseData, $status, $headers);
    }

    /**
     * Create a custom response with metadata
     */
    public static function withMeta($data, array $meta, int $status = 200, array $headers = [], array $context = []): self
    {
        $responseData = [
            'success' => true,
            'data' => $data,
            'meta' => $meta,
        ];

        $response = new self($responseData, $status, $headers);

        if (!empty($context)) {
            $response->setData($responseData);
            $response->setEncodingOptions(self::DEFAULT_ENCODING_OPTIONS | JSON_UNESCAPED_UNICODE);
        }

        return $response;
    }
}
