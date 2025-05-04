<?php

namespace App\Api\Request\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class SocialAuthRequest
{
    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'Provider is required')]
    #[Assert\Choice(choices: ['google', 'facebook', 'apple'], message: 'Invalid provider. Supported providers: google, facebook, apple')]
    private string $provider;

    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'Token is required')]
    private string $token;

    /**
     * Optional user data, required for Apple authentication
     * @var array|null
     */
    private ?array $userData = null;

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @param string $provider
     * @return self
     */
    public function setProvider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return self
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getUserData(): ?array
    {
        return $this->userData;
    }

    /**
     * @param array|null $userData
     * @return self
     */
    public function setUserData(?array $userData): self
    {
        $this->userData = $userData;
        return $this;
    }
} 