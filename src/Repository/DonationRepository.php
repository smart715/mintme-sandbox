<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Donation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    public function findAllUserRelated(User $user): array
    {
        $result = $this->createQueryBuilder('donation')
            ->where('donation.donor = :user')
            ->orWhere('donation.tokenCreator = :user')
            ->setParameter('user', $user);

        return $result->getQuery()->getResult();
    }
}
