<?php

namespace App\Api\Request\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class SocialConnectRequest
{
    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'Provider is required')]
    #[Assert\Choice(choices: ['google', 'facebook', 'apple'], message: 'Invalid provider. Supported providers: google, facebook, apple')]
    private string $provider;

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
} 