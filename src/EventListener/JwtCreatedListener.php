<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JwtCreatedListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $user = $event->getUser();

        // Add custom claims to the JWT token
        if ($user instanceof User) {
            // Add username to the payload
            $payload['username'] = $user->getUsername() ?? $user->getEmail(); // Fallback to email if username is null
            
            // Add additional user data if needed
            $payload['firstName'] = $user->getFirstName();
            $payload['lastName'] = $user->getLastName();
            $payload['id'] = $user->getId();
        }

        $event->setData($payload);
    }
} 