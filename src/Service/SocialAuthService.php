<?php

namespace App\Service;

use App\Api\Exception\Auth\MissingEmailExceptionAbstract;
use App\Api\Exception\Auth\SocialProviderException;
use App\Api\Response\Auth\SocialAuthResponse;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\AppleClient;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * Service for handling social authentication
 */
readonly class SocialAuthService
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    /**
     * Authenticate with Google token (mobile apps)
     * @throws SocialProviderException
     */
    public function authenticateWithGoogleToken(string $token): SocialAuthResponse
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');

        $accessToken = new AccessToken(['access_token' => $token]);
        $googleUser = $client->fetchUserFromToken($accessToken);

        // Process user data
        $user = $this->findOrCreateUserFromGoogle($googleUser);
        $jwtToken = $this->jwtManager->create($user);

        return SocialAuthResponse::fromUserAndToken($user, $jwtToken);
    }

    /**
     * Authenticate with Facebook token (mobile apps)
     * @throws SocialProviderException
     */
    public function authenticateWithFacebookToken(string $token): SocialAuthResponse
    {
        /** @var FacebookClient $client */
        $client = $this->clientRegistry->getClient('facebook');

        $accessToken = new AccessToken(['access_token' => $token]);
        $facebookUser = $client->fetchUserFromToken($accessToken);

        // Process user data
        $user = $this->findOrCreateUserFromFacebook($facebookUser);
        $jwtToken = $this->jwtManager->create($user);

        return SocialAuthResponse::fromUserAndToken($user, $jwtToken);
    }

    /**
     * Authenticate with Apple token (mobile apps)
     * @throws SocialProviderException
     */
    public function authenticateWithAppleToken(string $token, ?array $userData): SocialAuthResponse
    {
        /** @var AppleClient $client */
        $client = $this->clientRegistry->getClient('apple');

        $accessToken = new AccessToken(['access_token' => $token]);
        $appleUser = $client->fetchUserFromToken($accessToken);

        // Extract name data if provided
        $nameData = [
            'firstName' => $userData['name']['firstName'] ?? 'Apple',
            'lastName' => $userData['name']['lastName'] ?? 'User'
        ];

        // Process user data
        $user = $this->findOrCreateUserFromApple($appleUser, $nameData);
        $jwtToken = $this->jwtManager->create($user);

        return SocialAuthResponse::fromUserAndToken($user, $jwtToken);
    }

    /**
     * Find or create user from Google resource owner
     * @throws MissingEmailExceptionAbstract|SocialProviderException
     */
    private function findOrCreateUserFromGoogle(ResourceOwnerInterface $googleUser): User
    {
        $this->entityManager->beginTransaction();

        try {
            // Get user data from resource owner
            $userData = $googleUser->toArray();
            $googleId = $googleUser->getId();
            $email = $userData['email'] ?? '';

            if (empty($email)) {
                throw new MissingEmailExceptionAbstract('google');
            }

            $firstName = $userData['given_name'] ?? '';
            $lastName = $userData['family_name'] ?? '';
            $avatarUrl = $userData['picture'] ?? null;

            // Check if user exists by Google ID
            $user = $this->userRepository->findOneBy(['googleId' => $googleId]);

            if (!$user) {
                // Check if user exists by email
                $user = $this->userRepository->findOneByEmail($email);

                if ($user) {
                    // Update existing user with Google ID
                    $user->setGoogleId($googleId);
                    $user->setIsSocialLogin(true);

                    // Update avatar if user doesn't have one
                    if ($avatarUrl && !$user->getAvatarUrl()) {
                        $user->setAvatarUrl($avatarUrl);
                    }

                    // Update the updatedAt timestamp
                    $user->setUpdatedAt(new \DateTimeImmutable());
                } else {
                    // Create a new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setGoogleId($googleId);
                    $user->setFirstName($firstName);
                    $user->setLastName($lastName);

                    if ($avatarUrl) {
                        $user->setAvatarUrl($avatarUrl);
                    }

                    $user->setRoles(['ROLE_USER']);
                    $user->setIsSocialLogin(true);
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $this->entityManager->commit();

            return $user;
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            if ($e instanceof MissingEmailExceptionAbstract) {
                throw $e;
            }

            throw new SocialProviderException('google', 'Error processing Google account: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Find or create user from Facebook resource owner
     * @throws MissingEmailExceptionAbstract|SocialProviderException
     */
    private function findOrCreateUserFromFacebook(ResourceOwnerInterface $facebookUser): User
    {
        $this->entityManager->beginTransaction();

        try {
            // Get user data from resource owner
            $userData = $facebookUser->toArray();
            $facebookId = $facebookUser->getId();
            $email = $userData['email'] ?? '';

            if (empty($email)) {
                throw new MissingEmailExceptionAbstract('facebook');
            }

            $firstName = $userData['first_name'] ?? '';
            $lastName = $userData['last_name'] ?? '';
            $pictureData = $userData['picture']['data'] ?? null;
            $avatarUrl = $pictureData ? ($pictureData['url'] ?? null) : null;

            // Check if user exists by Facebook ID
            $user = $this->userRepository->findOneBy(['facebookId' => $facebookId]);

            if (!$user) {
                // Check if user exists by email
                $user = $this->userRepository->findOneByEmail($email);

                if ($user) {
                    // Update existing user with Facebook ID
                    $user->setFacebookId($facebookId);
                    $user->setIsSocialLogin(true);

                    // Update avatar if user doesn't have one
                    if ($avatarUrl && !$user->getAvatarUrl()) {
                        $user->setAvatarUrl($avatarUrl);
                    }

                    // Update the updatedAt timestamp
                    $user->setUpdatedAt(new \DateTimeImmutable());
                } else {
                    // Create a new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setFacebookId($facebookId);
                    $user->setFirstName($firstName);
                    $user->setLastName($lastName);

                    if ($avatarUrl) {
                        $user->setAvatarUrl($avatarUrl);
                    }

                    $user->setRoles(['ROLE_USER']);
                    $user->setIsSocialLogin(true);
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $this->entityManager->commit();

            return $user;
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            if ($e instanceof MissingEmailExceptionAbstract) {
                throw $e;
            }

            throw new SocialProviderException('facebook', 'Error processing Facebook account: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Find or create user from Apple resource owner
     * @throws MissingEmailExceptionAbstract|SocialProviderException
     */
    private function findOrCreateUserFromApple(ResourceOwnerInterface $appleUser, array $nameData): User
    {
        $this->entityManager->beginTransaction();

        try {
            $userId = $appleUser->getId();
            $userData = $appleUser->toArray();
            $email = $userData['email'] ?? null;

            // Check if user exists by Apple ID
            $user = $this->userRepository->findOneBy(['appleId' => $userId]);

            if (!$user) {
                // Check if user exists by email
                if ($email) {
                    $user = $this->userRepository->findOneByEmail($email);
                }

                if ($user) {
                    // Update existing user with Apple ID
                    $user->setAppleId($userId);
                    $user->setIsSocialLogin(true);

                    // Update the updatedAt timestamp
                    $user->setUpdatedAt(new \DateTimeImmutable());
                } else {
                    // If email is null, we can't create a user
                    if (!$email) {
                        throw new MissingEmailExceptionAbstract('apple');
                    }

                    // Create a new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setAppleId($userId);
                    $user->setFirstName($nameData['firstName']);
                    $user->setLastName($nameData['lastName']);
                    $user->setRoles(['ROLE_USER']);
                    $user->setIsSocialLogin(true);
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $this->entityManager->commit();

            return $user;
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            if ($e instanceof MissingEmailExceptionAbstract) {
                throw $e;
            }

            throw new SocialProviderException('apple', 'Error processing Apple account: ' . $e->getMessage(), $e);
        }
    }
}
