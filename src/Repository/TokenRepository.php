<?php declare(strict_types = 1);

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

    public function findByUrl(string $name): ?Token
    {
        return $this->createQueryBuilder('token')
            ->where('REPLACE(token.name, \' \', \'-\' ) = (:name)')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Token[] */
    public function findTokensByPattern(string $pattern): array
    {
        return $this->createQueryBuilder('token')
            ->where('LOWER(token.name) LIKE LOWER(:like)')
            ->setParameter('like', "$pattern%")
            ->orderBy('token.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
