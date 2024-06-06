<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\PromotionHistory;
use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RewardParticipant>
 * @codeCoverageIgnore
 */
class RewardParticipantRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RewardParticipant::class);
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
        return $this->createQueryBuilder('rp')
            ->join(Reward::class, 'r', Join::WITH, 'rp.reward = r.id')
            ->join(Token::class, 't', Join::WITH, 'r.token = t.id')
            ->join(Profile::class, 'p', Join::WITH, 't.profile = p.id')
            ->where('p.user = :userId OR rp.user = :userId')
            ->andWhere('rp.createdAt > :fromDate')
            ->setParameter('userId', $user->getId())
            ->setParameter('fromDate', $fromDate)
            ->orderBy('rp.createdAt', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findParticipantByUserAndReward(User $user, Reward $reward): ?RewardParticipant
    {
        return $this->findOneBy(['user' => $user, 'reward' => $reward], ['id' => 'DESC']);
    }

    public function findParticipantById(int $id): ?RewardParticipant
    {
        return $this->findOneBy(['id' => $id]);
    }
}
