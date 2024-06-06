<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\PendingTokenWithdraw;
use App\Entity\Token\Token;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class PendingTokenWithdrawRepository extends AbstractPendingWithdrawRepository
{
    public function __construct(
        ManagerRegistry $registry,
        int $expirationTime
    ) {
        parent::__construct($registry, PendingTokenWithdraw::class, $expirationTime);
    }

    /** @return PendingTokenWithdraw[] */
    public function getPending(User $user, int $offset, int $limit, DateTimeImmutable $fromDate): array
    {
        return $this->createQueryBuilder('ptw')
            ->where('ptw.user = :user')
            ->andWhere('ptw.date > :fromDate')
            ->orderBy('ptw.date', Criteria::DESC)
            ->setParameter('user', $user)
            ->setParameter('fromDate', $fromDate)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getPendingByToken(User $user, Token $token): array
    {
        $datetime = new DateTimeImmutable('now - ' . $this->expirationTime . ' seconds');

        return $this->createQueryBuilder('pending_token_withdraw')
            ->where('pending_token_withdraw.user = :user')
            ->andWhere('pending_token_withdraw.token = :token')
            ->andWhere('pending_token_withdraw.date > :expire')
            ->setParameter('user', $user)
            ->setParameter('token', $token)
            ->setParameter('expire', $datetime)
            ->getQuery()
            ->getResult();
    }
}
