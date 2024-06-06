<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Crypto;
use App\Entity\DepositHash;
use App\Entity\Token\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepositHash>
 * @codeCoverageIgnore
 */
class DepositHashRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepositHash::class);
    }

    public function findByHash(string $hash, Crypto $crypto, ?Token $token = null): ?DepositHash
    {
        return $this->findOneBy([
            'hash' => strtolower($hash),
            'crypto' => $crypto,
            'token' => $token,
        ]);
    }
}
