<?php

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\Token;
use Doctrine\ORM\EntityRepository;

class TokenRepository extends EntityRepository
{
    public function findByProfile(Profile $profile): ?Token
    {
        return $this->findOneBy(['profile' => $profile->getId()]);
    }

    public function findByName(string $name): ?Token
    {
        return $this->findOneBy(['name' => $name]);
    }
}
