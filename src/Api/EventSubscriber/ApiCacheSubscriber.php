<?php

namespace App\Api\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiCacheSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 10],
        ];
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
        $method = $request->getMethod();
        
        // Set cache control headers based on the request method
        if (in_array($method, ['GET', 'HEAD'])) {
            // GET and HEAD can be cached, but only in private caches (browsers)
            // Add max-age for browser caching
            $response->headers->set('Cache-Control', 'private, max-age=60');
            
            // Set ETag for cache validation
            if (!$response->headers->has('ETag')) {
                $etag = md5($response->getContent());
                $response->headers->set('ETag', '"' . $etag . '"');
            }
            
            // Set Last-Modified if not already set
            if (!$response->headers->has('Last-Modified')) {
                $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT');
            }
        } else {
            // POST, PUT, DELETE, etc. should not be cached
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
        
        // Add CORS headers if needed
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Version');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
} 