<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use Doctrine\ORM\EntityRepository;

class LockInRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function findByToken(Token $token): ?LockIn
    {
        return $this->findOneBy([ 'token' => $token ]);
    }

    /** @codeCoverageIgnore */
    public function findAllUnreleased(): array
    {
        return $this->createQueryBuilder('table')
            ->where('table.frozenAmount > 0')
            ->getQuery()
            ->getResult();
    }
}
