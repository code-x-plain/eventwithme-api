<?php

namespace App\Api\EventSubscriber;

use App\Api\Response\ApiResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private ParameterBagInterface $params;
    
    // Default values, can be overridden in parameters
    private int $limit = 60;
    private int $period = 60; // seconds
    
    public function __construct(
        RequestStack $requestStack,
        ParameterBagInterface $params
    ) {
        $this->requestStack = $requestStack;
        $this->params = $params;
        
        // Get rate limit parameters from config if available
        if ($this->params->has('api_rate_limit.limit')) {
            $this->limit = $this->params->get('api_rate_limit.limit');
        }
        
        if ($this->params->has('api_rate_limit.period')) {
            $this->period = $this->params->get('api_rate_limit.period');
        }
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }
    
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        
        $request = $event->getRequest();
        
        // Only apply to API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Skip rate limiting for some routes if needed
        if (in_array($request->getPathInfo(), ['/api/health', '/api/doc'])) {
            return;
        }
        
        $session = $this->requestStack->getSession();
        
        // In a real application, use the authenticated user's ID or API key
        // For anonymous users, use IP address (simplified for demonstration)
        $clientId = $request->getClientIp();
        
        $rateLimitKey = "rate_limit_{$clientId}";
        $timestamp = time();
        
        // Get current requests in the time window
        $requests = $session->get($rateLimitKey, []);
        
        // Filter out expired timestamps
        $requests = array_filter($requests, function ($time) use ($timestamp) {
            return $time > ($timestamp - $this->period);
        });
        
        // Check if limit exceeded
        if (count($requests) >= $this->limit) {
            $response = ApiResponse::error(
                'Rate limit exceeded',
                ['limit' => $this->limit, 'period' => $this->period, 'reset' => min($requests) + $this->period - $timestamp],
                Response::HTTP_TOO_MANY_REQUESTS
            );
            
            // Add rate limit headers
            $response->headers->set('X-RateLimit-Limit', $this->limit);
            $response->headers->set('X-RateLimit-Remaining', 0);
            $response->headers->set('X-RateLimit-Reset', min($requests) + $this->period - $timestamp);
            $response->headers->set('Retry-After', min($requests) + $this->period - $timestamp);
            
            $event->setResponse($response);
            return;
        }
        
        // Add current request timestamp to the list
        $requests[] = $timestamp;
        $session->set($rateLimitKey, $requests);
    }
    
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        
        $request = $event->getRequest();
        
        // Only apply to API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }
        
        $response = $event->getResponse();
        $session = $this->requestStack->getSession();
        
        // In a real application, use the authenticated user ID or API key
        $clientId = $request->getClientIp();
        
        $rateLimitKey = "rate_limit_{$clientId}";
        $requests = $session->get($rateLimitKey, []);
        
        if (!empty($requests)) {
            // Add rate limit headers to all API responses
            $remaining = max(0, $this->limit - count($requests));
            $response->headers->set('X-RateLimit-Limit', $this->limit);
            $response->headers->set('X-RateLimit-Remaining', $remaining);
            
            if (count($requests) > 0) {
                $resetTime = min($requests) + $this->period - time();
                $response->headers->set('X-RateLimit-Reset', max(0, $resetTime));
            }
        }
    }
} 