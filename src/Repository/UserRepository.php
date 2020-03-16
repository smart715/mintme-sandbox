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

    /** @codeCoverageIgnore */
    public function findByDomain(string $domain): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.email LIKE :domain')
            ->setParameter('domain', '%@'.$domain)
            ->getQuery()
            ->execute();
    }

    /** @codeCoverageIgnore */
    public function checkExistCanonicalEmail(string $email): bool
    {
        $user = $this->createQueryBuilder('u')
            ->Where("u.emailCanonical LIKE :email")
            ->setParameter('email', $email)
            ->getQuery()
            ->getArrayResult();

            return 0 != count($user);
    }
}
