<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\Token;
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
        EntityManagerInterface $entityManager,
        ProfileFetcherInterface $profileFetcher
    ) {
        $this->repository = $entityManager->getRepository(Token::class);
        $this->profileFetcher = $profileFetcher;
    }

    public function createToken(): Token
    {
        $profile = $this->getProfile();
        $address = '0xstub0123456789'; // FIXME: generate unique token address with webchain wallet

        return new Token($profile, $address);
    }

    public function findByName(string $name): ?Token
    {
        return $this->repository->findByName($name);
    }

    public function getOwnToken(): ?Token
    {
        $profile = $this->getProfile();
        if (null === $profile)
            return null;

        return $this->repository->findByProfile($profile);
    }

    private function getProfile(): ?Profile
    {
        return $this->profileFetcher->fetchProfile();
    }
}
