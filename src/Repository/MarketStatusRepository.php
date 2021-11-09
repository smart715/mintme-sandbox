<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Utils\Symbols;
use Doctrine\Common\Collections\Criteria;
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
            ->leftJoin('m.quoteCrypto', 'qc')
            ->andWhere('qt.name = :quote OR qc.symbol = :quote')
            ->setParameter('base', $base)
            ->setParameter('quote', $quote)
            ->getQuery()
            ->getResult()[0] ?? null;
    }

    /** @codeCoverageIgnore */
    public function getTokenWEBMarkets(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.crypto', 'c')
            ->leftJoin('m.quoteToken', 'qt')
            ->where('qt IS NOT NULL')
            ->andWhere('c.symbol = :web')
            ->setParameter('web', Symbols::WEB)
            ->getQuery()
            ->getResult();
    }

    /** @codeCoverageIgnore */
    public function getExchangeableCryptoMarkets(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.crypto', 'c')
            ->leftJoin('m.quoteCrypto', 'qc')
            ->where('qc IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    public function getExpired(): array
    {
        return $this->createQueryBuilder('ms')
            ->where('ms.expires is not null and ms.expires < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function getCryptoAndDeployedTokenMarketStatuses(?int $offset = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('ms')
            ->leftJoin('ms.quoteToken', 'qt')
            ->leftJoin('qt.crypto', 'qt_crypto')
            ->where('ms.quoteToken IS NULL')
            ->orWhere('qt.address IS NOT NULL AND qt.address != :pending AND qt.isBlocked = false')
            ->orWhere('qt_crypto.symbol IS NOT NULL AND qt_crypto.symbol = :ethSymbol')
            ->setParameter('pending', Token::PENDING_ADDR)
            ->setParameter('ethSymbol', Symbols::ETH)
            ->orderBy('ms.lastPrice', Criteria::DESC);

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
