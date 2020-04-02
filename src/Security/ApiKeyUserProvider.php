<?php declare(strict_types = 1);

namespace App\Security;

use App\Manager\ApiKeyManagerInterface;
use App\Manager\UserManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiKeyUserProvider implements UserProviderInterface
{
    /** @var UserManagerInterface */
    private $userManager;

    /** @var ApiKeyManagerInterface */
    private $keyManager;

    public function __construct(UserManagerInterface $userManager, ApiKeyManagerInterface $keyManager)
    {
        $this->userManager = $userManager;
        $this->keyManager = $keyManager;
    }

    public function getUsernameForApiKey(string $apiKey): ?string
    {
        $key = $this->keyManager->findApiKey($apiKey);

        return $key ?
            $key->getUser()->getUsername() :
            null;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function loadUserByUsername($username): ?UserInterface
    {
        return $this->userManager->findUserByEmail($username);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function refreshUser(UserInterface $user): void
    {
        throw new UnsupportedUserException();
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function supportsClass($class): bool
    {
        return User::class === $class;
    }
}
