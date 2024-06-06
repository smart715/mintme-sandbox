<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\PromotionHistory;
use App\Entity\Token\Token;
use App\Entity\Token\TokenPromotion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TokenPromotion>
 * @codeCoverageIgnore
 */
class TokenPromotionRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenPromotion::class);
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
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.createdAt > :fromDate')
            ->setParameter('user', $user)
            ->setParameter('fromDate', $fromDate)
            ->orderBy('p.createdAt', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return TokenPromotion[] */
    public function findActivePromotionsByToken(Token $token): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.token = :token')
            ->andWhere('p.endDate > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /** @return TokenPromotion[] */
    public function findActivePromotions(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.endDate > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }
}
