<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\CommentTip;
use App\Entity\PromotionHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentTip>
 * @codeCoverageIgnore
 */
class CommentTipRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentTip::class);
    }

    /**
     * @return PromotionHistory[]
     */
    public function getPromotionHistoryByUserAndToken(
        User $user,
        int $offset,
        int $limit,
        \DateTimeImmutable $fromDate
    ): array {
        return $this->createQueryBuilder('ct')
            // show fee only for user who gave the tip
            ->where('ct.user = :userId OR (ct.commentAuthor = :userId AND ct.tipType != :type)')
            ->andWhere('ct.createdAt > :fromDate')
            ->setParameter('userId', $user->getId())
            ->setParameter('type', CommentTip::FEE_TIP_TYPE)
            ->setParameter('fromDate', $fromDate)
            ->orderBy('ct.createdAt', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalFeesPerCrypto(\DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        return $this->createQueryBuilder('ct')
            ->select('SUM(ct.amount) as total, ct.currency as symbol, count(ct.id) as count')
            ->where('ct.tipType = :type')
            ->andWhere('ct.createdAt > :dateFrom')
            ->andWhere('ct.createdAt < :dateTo')
            ->setParameter('type', CommentTip::FEE_TIP_TYPE)
            ->setParameter('dateFrom', $dateFrom)
            ->setParameter('dateTo', $dateTo)
            ->groupBy('ct.currency')
            ->getQuery()
            ->getResult();
    }
}
