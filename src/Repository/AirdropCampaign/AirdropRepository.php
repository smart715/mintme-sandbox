<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Airdrop;
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
            ->andWhere('a.status = :status ANd a.endDate < CURRENT_TIMESTAMP()')
            ->setParameter('status', Airdrop::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }
}
