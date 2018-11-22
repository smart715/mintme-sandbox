<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByIds(array $userIds): array
    {
        return $this->createQueryBuilder('user')
            ->where('user.id IN(:userIds)')
            ->setParameter('userIds', $userIds)
            ->getQuery()
            ->execute()
        ;
    }

    public function findByHash(string $hash): ?User
    {
        return $this->findOneBy(['hash' => $hash]);
    }
}
