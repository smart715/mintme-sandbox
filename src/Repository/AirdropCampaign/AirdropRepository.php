<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Airdrop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Airdrop|null find($id, $lockMode = null, $lockVersion = null)
 * @method Airdrop|null findOneBy(array $criteria, array $orderBy = null)
 * @method Airdrop[]    findAll()
 * @method Airdrop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AirdropRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Airdrop::class);
    }
}
