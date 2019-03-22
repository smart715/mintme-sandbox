<?php declare(strict_types = 1);

namespace App\Fetcher;

use App\Entity\Profile;
use App\Manager\ProfileManagerInterface;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileFetcher implements ProfileFetcherInterface
{
    /** @var ProfileManagerInterface */
    private $profileManager;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(
        ProfileManagerInterface $profileManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->profileManager = $profileManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function fetchProfile(): ?Profile
    {
        if (null === $this->tokenStorage->getToken()) {
            throw new RuntimeException('Not authenticated.');
        }

        return $this->profileManager->getProfile(
            $this->tokenStorage->getToken()->getUser()
        );
    }
}
