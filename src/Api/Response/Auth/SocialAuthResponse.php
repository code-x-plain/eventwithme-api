<?php

namespace App\Api\Response\Auth;

use App\Entity\User;

class SocialAuthResponse
{
    /**
     * @var string
     */
    private string $token;

    /**
     * @var UserResponse
     */
    private UserResponse $user;

    /**
     * Create response from user entity and JWT token
     */
    public static function fromUserAndToken(User $user, string $token): self
    {
        $response = new self();
        $response->token = $token;
        $response->user = UserResponse::fromEntity($user);
        
        return $response;
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
     * @return UserResponse
     */
    public function getUser(): UserResponse
    {
        return $this->user;
    }

    /**
     * @param UserResponse $user
     * @return self
     */
    public function setUser(UserResponse $user): self
    {
        $this->user = $user;
        return $this;
    }
} 