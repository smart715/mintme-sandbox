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
}
