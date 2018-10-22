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

    /**
     * @param array|string|null $hash
     * @return User|null
     */
    public function findByHash($hash): ?User
    {
        return $this->findOneBy(['hash' => $hash]);
    }
}
