<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Crypto;
use Doctrine\ORM\EntityRepository;

class CryptoRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function getBySymbol(string $symbol, bool $showHidden): ?Crypto
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.symbol = :symbol')
            ->setParameter('symbol', $symbol);

        if (!$showHidden) {
            $query->andWhere('c.tradable = 1 OR c.exchangeble = 1');
        }

        return $query
            ->getQuery()
            ->getResult()[0] ?? null;
    }
}
