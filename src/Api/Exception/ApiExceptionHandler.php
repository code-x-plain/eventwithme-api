<?php

namespace App\Api\Exception;

use App\Api\Response\ApiResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }
        
        $exception = $event->getThrowable();
        
        $response = $this->createApiResponse($exception);
        $event->setResponse($response);
    }
    
    private function createApiResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $errors = null;
        
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }
        
        // In production, hide internal errors
        $message = $exception->getMessage();
        if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR && $_ENV['APP_ENV'] === 'prod') {
            $message = 'Internal server error';
        } else if ($exception instanceof ApiException) {
            $errors = $exception->getErrors();
        }
        
        // Log exception for internal server errors
        if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
            error_log($exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
        
        return ApiResponse::error($message, $errors, $statusCode);
    }
} 