<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\MarketStatus;
use Doctrine\ORM\EntityRepository;

class MarketStatusRepository extends EntityRepository
{
    public function findByName(string $tokenName): ?MarketStatus
    {
        return $this->findOneBy(['tokenName' => $tokenName]);
    }
}
