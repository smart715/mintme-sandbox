<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class TokenManager implements TokenManagerInterface
{
    /** @var TokenRepository */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Token::class);
    }

    public function findByName(string $name): ?Token
    {
        return $this->repository->findByName($name);
    }

    public function getOwnToken(User $user): ?Token
    {
        $profile = $user->getProfile();
        if (null === $profile)
            return null;

        return $profile->getToken();
    }
}
