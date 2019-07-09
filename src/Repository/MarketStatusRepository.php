<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\MarketStatus;
use Doctrine\ORM\EntityRepository;

class MarketStatusRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function findByName(string $tokenName): ?MarketStatus
    {
        return $this->findOneBy(['tokenName' => $tokenName]);
    }

    /** @codeCoverageIgnore */
    public function findByBaseQuoteNames(string $base, string $quote): ?MarketStatus
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.crypto', 'c')
            ->where('c.symbol = :base')
            ->leftJoin('m.quoteToken', 'qt')
            ->andWhere('qt.name = :quote')
            ->leftJoin('m.quoteCrypto', 'qc')
            ->orWhere('qc.symbol = :quote')
            ->setParameter('base', $base)
            ->setParameter('quote', $quote)
            ->getQuery()
            ->getResult()[0] ?? null;
    }
}
