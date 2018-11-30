<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\Token\Token;
use Doctrine\ORM\EntityRepository;

class TokenRepository extends EntityRepository
{
    public function findByName(string $name): ?Token
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** @return Token[] */
    public function findTokensByPattern(string $pattern): array
    {
        return $this->createQueryBuilder('token')
            ->andwhere('token.name LIKE :like')
            ->orwhere('token.name LIKE :space_like')
            ->setParameter('like', "$pattern%")
            ->setParameter('space_like', " $pattern%")
            ->orderBy('token.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
