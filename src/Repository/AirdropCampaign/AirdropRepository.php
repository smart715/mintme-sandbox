<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropReferralCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AirdropRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Airdrop::class);
    }

    public function getOutdatedAirdrops(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status AND a.endDate < UTC_TIMESTAMP()')
            ->setParameter('status', Airdrop::STATUS_ACTIVE)
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
}
