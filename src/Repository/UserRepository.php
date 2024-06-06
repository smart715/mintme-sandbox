<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

/**
 * @extends ServiceEntityRepository<User>
 * @codeCoverageIgnore
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByHash(string $hash): ?User
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    public function findByReferralCode(?string $referralCode): ?User
    {
        return $this->findOneBy([ 'referralCode' => $referralCode ]);
    }

    public function findReferences(?int $userId): ?array
    {
        return $this->findBy([ 'referencerId' => $userId ]);
    }

    public function findByDomain(string $domain): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.email LIKE :domain')
            ->setParameter('domain', '%@'.$domain)
            ->getQuery()
            ->execute();
    }

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
