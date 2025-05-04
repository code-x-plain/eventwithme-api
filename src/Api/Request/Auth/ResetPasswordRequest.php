<?php

namespace App\Api\Request\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordRequest
{
    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'Token is required')]
    private string $token;

    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters long')]
    private string $password;

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
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }
} 