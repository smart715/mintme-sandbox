<?php

namespace App\Repository;

use App\Entity\GoogleAuthenticatorEntry;
use Doctrine\ORM\EntityRepository;

class GoogleAuthenticatorEntryRepository extends EntityRepository
{
    public function getGoogleAuthenticator(int $userId): GoogleAuthenticatorEntry
    {
        return $this->findOneBy(['user' => $userId]) ?? new GoogleAuthenticatorEntry();
    }
}
