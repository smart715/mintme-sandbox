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

    public function findByHash(string $hash): ?User
    {
        return $this->findOneBy(['hash' => $hash]);
    }
    
    public function findByReferralCode(string $referralCode): ?User
    {
        return $this->findOneBy([ 'referralCode' => $referralCode ]);
    }
    
    public function findReferences(?int $userId): ?array
    {
        return $this->findBy([ 'referencerId' => $userId ]);
    }
}
