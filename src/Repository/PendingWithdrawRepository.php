<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;

class PendingWithdrawRepository extends EntityRepository
{
    /** @var int */
    public $expireHours;

    /** @codeCoverageIgnore */
    public function getWithdrawByHash(string $hash): ?PendingWithdrawInterface
    {
        $datetime = new DateTimeImmutable('now - '.$this->expireHours.' hours');

        return $this->createQueryBuilder('t')
            ->where('t.hash = :hash AND t.date > :expire')
            ->setParameter('hash', $hash)
            ->setParameter('expire', $datetime)
            ->getQuery()
            ->getResult()[0] ?? null;
    }
}
