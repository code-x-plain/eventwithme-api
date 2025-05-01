<?php

namespace App\Api\EventSubscriber;

use App\Api\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiVersionSubscriber implements EventSubscriberInterface
{
    // Define versions with their support status
    private const VERSIONS = [
        '1.0' => ['status' => 'current', 'sunset_date' => null],
        '1.1' => ['status' => 'beta', 'sunset_date' => null],
        '0.9' => ['status' => 'deprecated', 'sunset_date' => '2024-12-31'],
    ];
    
    private const DEFAULT_VERSION = '1.0';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only process API routes
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api')) {
            return;
        }
        
        // Initialize with default version
        $version = self::DEFAULT_VERSION;
        $versionSource = 'default';
        
        // 1. Check URL path for version (e.g., /api/v1.1/events)
        if (preg_match('#^/api/v([0-9.]+)/#', $path, $matches)) {
            $urlVersion = $matches[1];
            if ($this->isVersionSupported($urlVersion)) {
                $version = $urlVersion;
                $versionSource = 'url';
                
                // Rewrite the path to remove version segment
                $newPath = preg_replace('#^/api/v([0-9.]+)/#', '/api/', $path);
                $request->attributes->set('_route_params', array_merge(
                    $request->attributes->get('_route_params', []),
                    ['_original_path' => $path]
                ));
                $request->server->set('REQUEST_URI', $newPath);
                $request->initialize(
                    $request->query->all(),
                    $request->request->all(),
                    $request->attributes->all(),
                    $request->cookies->all(),
                    $request->files->all(),
                    array_merge($request->server->all(), ['REQUEST_URI' => $newPath]),
                    $request->getContent()
                );
            }
        }
        
        // 2. Check for version in Accept header: application/json; version=1.0
        $acceptHeader = $request->headers->get('Accept');
        if ($acceptHeader && $versionSource === 'default') {
            preg_match('/version=([0-9.]+)/', $acceptHeader, $matches);
            if (!empty($matches[1]) && $this->isVersionSupported($matches[1])) {
                $version = $matches[1];
                $versionSource = 'accept-header';
            }
        }
        
        // 3. Check for version in X-API-Version header (highest priority)
        $apiVersionHeader = $request->headers->get('X-API-Version');
        if ($apiVersionHeader && $this->isVersionSupported($apiVersionHeader)) {
            $version = $apiVersionHeader;
            $versionSource = 'x-api-version-header';
        }
        
        // Check if version is deprecated and warn or block
        if (isset(self::VERSIONS[$version]) && self::VERSIONS[$version]['status'] === 'deprecated') {
            // Add a warning header for deprecated versions
            $request->attributes->set('api_version_deprecated', true);
            $sunsetDate = self::VERSIONS[$version]['sunset_date'] ?? null;
            
            if ($sunsetDate) {
                $request->attributes->set('api_version_sunset', $sunsetDate);
                
                // If sunset date has passed, block the request
                if (strtotime($sunsetDate) < time()) {
                    throw new ApiException(
                        "API version {$version} has been sunset on {$sunsetDate}. Please upgrade to a supported version.",
                        ['supported_versions' => array_keys(array_filter(self::VERSIONS, function($v) {
                            return $v['status'] !== 'deprecated' || ($v['sunset_date'] && strtotime($v['sunset_date']) > time());
                        }))],
                        Response::HTTP_GONE
                    );
                }
            }
        }
        
        // Set API version info as request attributes for later use
        $request->attributes->set('api_version', $version);
        $request->attributes->set('api_version_source', $versionSource);
        $request->attributes->set('api_version_status', self::VERSIONS[$version]['status'] ?? 'unknown');
    }
    
    /**
     * Check if a version is supported
     */
    private function isVersionSupported(string $version): bool
    {
        return isset(self::VERSIONS[$version]);
    }
} 