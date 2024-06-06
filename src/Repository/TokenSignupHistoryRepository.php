<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\PromotionHistory;
use App\Entity\Token\Token;
use App\Entity\TokenSignupHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TokenSignupHistory>
 * @codeCoverageIgnore
 */
class TokenSignupHistoryRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenSignupHistory::class);
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
        return $this->createQueryBuilder('ap')
            ->join(Token::class, 't', Join::WITH, 'ap.token = t.id')
            ->join(Profile::class, 'p', Join::WITH, 't.profile = p.id')
            ->where('p.user = :userId OR ap.user = :userId')
            ->andWhere('ap.createdAt > :fromDate')
            ->setParameter('userId', $user->getId())
            ->setParameter('fromDate', $fromDate)
            ->orderBy('ap.createdAt', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndToken(User $user, Token $token): ?TokenSignupHistory
    {
        return $this->findOneBy([
            'user' => $user,
            'token' => $token,
        ]);
    }
}
