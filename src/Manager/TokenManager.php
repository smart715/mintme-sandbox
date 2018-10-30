<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Fetcher\ProfileFetcherInterface;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class TokenManager implements TokenManagerInterface
{
    /** @var TokenRepository */
    private $repository;

    /** @var ProfileFetcherInterface */
    private $profileFetcher;

    public function __construct(
        EntityManagerInterface $em,
        ProfileFetcherInterface $profileFetcher
    ) {
        $this->repository = $em->getRepository(Token::class);
        $this->profileFetcher = $profileFetcher;
    }

    public function findByName(string $name): ?Token
    {
        return $this->repository->findByName($name);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function getOwnToken(): ?Token
    {
        $profile = $this->getProfile();

        if (null === $profile) {
            return null;
        }

        return $profile->getToken();
    }

    private function getProfile(): ?Profile
    {
        return $this->profileFetcher->fetchProfile();
    }
}
