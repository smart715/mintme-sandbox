<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /** @codeCoverageIgnore */
    public function findByHash(string $hash): ?User
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    /** @codeCoverageIgnore */
    public function findByReferralCode(?string $referralCode): ?User
    {
        return $this->findOneBy([ 'referralCode' => $referralCode ]);
    }

    /** @codeCoverageIgnore */
    public function findReferences(?int $userId): ?array
    {
        return $this->findBy([ 'referencerId' => $userId ]);
    }
}
