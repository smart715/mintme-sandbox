<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Participant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Participant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Participant[]    findAll()
 * @method Participant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Participant::class);
    }
}
