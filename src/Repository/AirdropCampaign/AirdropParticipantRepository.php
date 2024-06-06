<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Profile;
use App\Entity\PromotionHistory;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\PromotionHistoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AirdropParticipant>
 * @codeCoverageIgnore
 */
class AirdropParticipantRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AirdropParticipant::class);
    }

    public function getParticipantByUserAndAirdrop(
        User $user,
        Airdrop $airdrop,
        ?User $referral = null
    ): ?AirdropParticipant {
        return $this->findOneBy([
            'user' => $user,
            'airdrop' => $airdrop,
            'referral' => $referral,
        ]);
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
            ->join(Airdrop::class, 'a', Join::WITH, 'ap.airdrop = a.id')
            ->join(Token::class, 't', Join::WITH, 'a.token = t.id')
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

    public function hasAirdropReward(User $user, Token $token): bool
    {
        return (bool)$this->createQueryBuilder('ap')
            ->join(Airdrop::class, 'a', Join::WITH, 'ap.airdrop = a.id')
            ->where('ap.user = :userId')
            ->andWhere('a.token = :tokenId')
            ->setParameter('userId', $user->getId())
            ->setParameter('tokenId', $token->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }
}
