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
        /** @var CryptoRepository $repository */
        $repository = $entityManager->getRepository(Crypto::class);

        $this->repository = $repository;
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

    public function findAllIndexed(string $index, bool $array = false): array
    {
        $query = $this->repository->createQueryBuilder('c', "c.{$index}")->getQuery();

        return $array
            ? $query->getArrayResult()
            : $query->getResult();
    }
}
