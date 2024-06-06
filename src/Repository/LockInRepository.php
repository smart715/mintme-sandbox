<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LockIn>
 * @codeCoverageIgnore
 */
class LockInRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LockIn::class);
    }

    public function findByToken(Token $token): ?LockIn
    {
        return $this->findOneBy([ 'token' => $token ]);
    }

    public function findAllUnreleased(): array
    {
        return $this->createQueryBuilder('table')
            ->where('table.frozenAmount > 0')
            ->getQuery()
            ->getResult();
    }
}
