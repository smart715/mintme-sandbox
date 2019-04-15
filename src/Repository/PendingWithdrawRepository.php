<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\PendingWithdraw;
use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;

class PendingWithdrawRepository extends EntityRepository
{
    public function getWithdrawByHash(string $hash): ?PendingWithdraw
    {
        $datetime = new DateTimeImmutable('now - '.PendingWithdraw::EXPIRES_HOURS.' hours');

        return $this->createQueryBuilder('t')
            ->where('t.hash = :hash AND t.date > :expire')
            ->setParameter('hash', $hash)
            ->setParameter('expire', $datetime)
            ->getQuery()
            ->getResult()[0] ?? null;
    }
}
