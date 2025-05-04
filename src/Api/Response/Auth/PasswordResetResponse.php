<?php

namespace App\Api\Response\Auth;

class PasswordResetResponse
{
    /**
     * @var bool
     */
    private bool $success;

    /**
     * @var string|null
     */
    private ?string $message = null;

    /**
     * Create a success response
     */
    public static function success(string $message = null): self
    {
        $response = new self();
        $response->success = true;
        $response->message = $message ?? 'Password reset request processed successfully';

        return $response;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return self
     */
    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     * @return self
     */
    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }
} 