<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\GoogleAuthenticatorEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GoogleAuthenticatorEntry>
 * @codeCoverageIgnore
 */
class GoogleAuthenticatorEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GoogleAuthenticatorEntry::class);
    }

    public function getGoogleAuthenticator(int $userId): GoogleAuthenticatorEntry
    {
        return $this->findOneBy(['user' => $userId]) ?? new GoogleAuthenticatorEntry();
    }
}
