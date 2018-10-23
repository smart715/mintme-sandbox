<?php

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
        return $this->repository->getBySymbol($symbol);
    }
}
