<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Crypto;
use App\Entity\PendingWithdraw;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class PendingWithdrawRepository extends AbstractPendingWithdrawRepository
{
    public function __construct(
        ManagerRegistry $registry,
        int $expirationTime
    ) {
        parent::__construct($registry, PendingWithdraw::class, $expirationTime);
    }

    /** @return PendingWithdraw[] */
    public function getPending(User $user, int $offset, int $limit, DateTimeImmutable $fromDate): array
    {
        return $this->createQueryBuilder('pw')
            ->where('pw.user = :user')
            ->andWhere('pw.date > :fromDate')
            ->orderBy('pw.date', Criteria::DESC)
            ->setParameter('user', $user)
            ->setParameter('fromDate', $fromDate)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getPendingByCrypto(User $user, Crypto $crypto): array
    {
        $datetime = new DateTimeImmutable('now - ' . $this->expirationTime . ' seconds');

        return $this->createQueryBuilder('pending_withdraw')
            ->where('pending_withdraw.user = :user')
            ->andWhere('pending_withdraw.crypto = :crypto')
            ->andWhere('pending_withdraw.date > :expire')
            ->setParameter('user', $user)
            ->setParameter('crypto', $crypto)
            ->setParameter('expire', $datetime)
            ->getQuery()
            ->getResult();
    }
}
