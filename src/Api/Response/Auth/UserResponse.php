<?php

namespace App\Api\Response\Auth;

use App\Entity\User;

class UserResponse
{
    /**
     * @var int
     */
    private int $id;

    /**
     * @var string
     */
    private string $email;

    /**
     * @var string|null
     */
    private ?string $username = null;

    /**
     * @var string|null
     */
    private ?string $firstName = null;

    /**
     * @var string|null
     */
    private ?string $lastName = null;

    /**
     * @var string|null
     */
    private ?string $avatarUrl = null;

    /**
     * @var array
     */
    private array $roles;

    /**
     * @var string|null
     */
    private ?string $createdAt = null;

    /**
     * @var string|null
     */
    private ?string $phoneNumber = null;

    /**
     * Create response from user entity
     */
    public static function fromEntity(User $user): self
    {
        $response = new self();
        $response->id = $user->getId();
        $response->email = $user->getEmail();
        $response->username = $user->getUsername();
        $response->firstName = $user->getFirstName();
        $response->lastName = $user->getLastName();
        $response->avatarUrl = $user->getAvatarUrl();
        $response->roles = $user->getRoles();
        $response->createdAt = $user->getCreatedAt()?->format(\DateTimeInterface::RFC3339);
        $response->phoneNumber = $user->getPhoneNumber();

        return $response;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

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

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     * @return self
     */
    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    /**
     * @param string|null $firstName
     * @return self
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }
    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    /**
     * @param string|null $lastName
     * @return self
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    /**
     * @param string|null $avatarUrl
     * @return self
     */
    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * @param string|null $createdAt
     * @return self
     */
    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     * @return self
     */
    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }
}
