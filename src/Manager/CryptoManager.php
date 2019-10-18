<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Repository\CryptoRepository;
use Doctrine\ORM\EntityManagerInterface;

class CryptoManager implements CryptoManagerInterface
{
    /** @var CryptoRepository */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Crypto::class);
    }

    public function findBySymbol(string $symbol): ?Crypto
    {
        return $this->repository->getBySymbol(strtoupper($symbol));
    }

    /** {@inheritdoc} */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findAllIndexed(string $index): array
    {
        return $this->repository->createQueryBuilder('c', "c.{$index}")
            ->getQuery()
            ->getArrayResult();
    }
}
