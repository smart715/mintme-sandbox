<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Crypto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Crypto>
 * @codeCoverageIgnore
 */
class CryptoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Crypto::class);
    }

    public function getBySymbol(string $symbol): ?Crypto
    {
        return $this->findOneBy(['symbol' => $symbol]);
    }

    /** {@inheritdoc} */
    public function findAll()
    {
        return $this->findBy([], ['id' => Criteria::ASC]);
    }
}
