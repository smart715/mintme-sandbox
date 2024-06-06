<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropAction;
use App\Entity\AirdropCampaign\AirdropReferralCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Airdrop>
 * @codeCoverageIgnore
 */
class AirdropRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Airdrop::class);
    }

    public function getOutdatedAirdrops(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status AND a.endDate < :now')
            ->setParameter('status', Airdrop::STATUS_ACTIVE)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function deleteReferralCodes(Airdrop $airdrop): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete(AirdropReferralCode::class, 'arc')
            ->where('arc.airdrop = :airdrop')
            ->setParameter('airdrop', $airdrop)
            ->getQuery()
            ->execute();
    }

    public function findBySingleActionType(int $actionType = 6): array
    {
        $qb = $this->createQueryBuilder('a');
        $subquery = $this->createQueryBuilder('a2')
            ->join('a2.actions', 'action2')
            ->where('action2.type = :type')
            ->setParameter('type', $actionType)
            ->getQuery()
            ->getDQL();
        $qb->join('a.actions', 'action1')
            ->groupBy('a.id')
            ->having('COUNT(action1) = 1')
            ->andWhere('a.status = 1')
            ->andWhere($qb->expr()->in('a.id', $subquery))
            ->setParameter('type', $actionType);

        return $qb->getQuery()->getResult();
    }

    public function deleteAirdropActions(int $actionType = 6): int
    {
        $entityManager = $this->getEntityManager();
    
        $queryBuilder = $entityManager->createQueryBuilder();
        $queryBuilder
            ->delete(AirdropAction::class, 'aidrop_action')
            ->andWhere('aidrop_action.type = :type')
            ->setParameter('type', $actionType)
            ->andWhere(
                $queryBuilder->expr()->in(
                    'aidrop_action.airdrop',
                    $entityManager->createQueryBuilder()
                        ->select('airdrop')
                        ->from(Airdrop::class, 'airdrop')
                        ->where('airdrop.status = 1')
                        ->getDQL()
                )
            );
    
        return $queryBuilder->getQuery()->execute();
    }
}
