<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Crypto;
use App\Entity\WrappedCryptoToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WrappedCryptoToken>
 * @codeCoverageIgnore
 */
class WrappedCryptoTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WrappedCryptoToken::class);
    }

    public function findByCryptoAndDeploy(Crypto $crypto, Crypto $cryptoDeploy): ?WrappedCryptoToken
    {
        return $this->findOneBy([
            'crypto' => $crypto,
            'cryptoDeploy' => $cryptoDeploy,
        ]);
    }

    public function findNativeBlockchainCrypto(Crypto $cryptoDeploy): ?WrappedCryptoToken
    {
        return $this->findOneBy([
            'cryptoDeploy' => $cryptoDeploy,
            'address' => null,
        ]);
    }
}
