<?php

namespace App\Api\Request\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RequestPasswordResetRequest
{
    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Email format is not valid')]
    private string $email;

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
} 