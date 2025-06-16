<?php

namespace App\Api\Response\Auth;

class SocialConnectResponse
{
    /**
     * @var string
     */
    private string $redirectUrl;

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $redirectUrl
     * @return self
     */
    public function setRedirectUrl(string $redirectUrl): self
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }
} 