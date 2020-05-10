<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\PendingWithdrawInterface;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

abstract class AbstractPendingWithdrawRepository extends ServiceEntityRepository
{
    /** @var int */
    protected $expireHours;

    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
        int $expireHours
    ) {
        $this->expireHours = $expireHours;
        parent::__construct($registry, $entityClass);
    }

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
