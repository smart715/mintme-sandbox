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

    public function findBySymbol(string $symbol, bool $showHidden = false): ?Crypto
    {
        return $this->repository->getBySymbol(strtoupper($symbol), $showHidden);
    }

    /** {@inheritdoc} */
    public function findAll(bool $showHidden = false): array
    {
        $cryptoArray = $this->repository->findAll();

        if (!$showHidden) {
            $cryptoArray = array_filter(
                $cryptoArray,
                static fn (Crypto $crypto) => $crypto->isExchangeble() || $crypto->isTradable()
            );
        }

        return $cryptoArray;
    }

    public function findAllIndexed(string $index, bool $array = false, bool $showHidden = false): array
    {
        $query = $this->repository->createQueryBuilder('c', "c.{$index}");

        if (!$showHidden) {
            $query
                ->andWhere('c.tradable = 1 OR c.exchangeble = 1');
        }

        return $array
            ? $query->getQuery()->getArrayResult()
            : $query->getQuery()->getResult();
    }
}
