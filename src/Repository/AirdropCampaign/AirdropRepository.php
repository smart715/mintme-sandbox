<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Airdrop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AirdropRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Airdrop::class);
    }

    public function updateOutdatedAirdrops(): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->executeUpdate('
            UPDATE airdrop
            SET status = 0
            WHERE status = 1 AND end_date IS NOT NULL AND end_date < CURRENT_TIMESTAMP();
        ');
    }
}
