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
        $userOwnToken = $user->getProfile()->getMintmeToken();

        $result = $this->createQueryBuilder('donation')
            ->where('donation.donor = :donor')
            ->setParameter('donor', $user);

        if ($userOwnToken) {
            $result
                ->orWhere('donation.tokenCreator = :tokenCreator')
                ->setParameter('tokenCreator', $userOwnToken->getOwner());
        }

        return $result->getQuery()->getResult();
    }
}
