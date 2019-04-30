<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Repository\MarketStatusRepository;
use Doctrine\ORM\EntityManagerInterface;

class MarketStatusManager implements MarketStatusManagerInterface
{
    /** @var MarketStatusRepository */
    protected $repository;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    public function __construct(EntityManagerInterface $em, CryptoManagerInterface $cryptoManager)
    {
        $this->repository = $em->getRepository(MarketStatus::class);
        $this->cryptoManager = $cryptoManager;
    }

    public function getMarketsInfo(): array
    {
        $marketsInfo = [];

        /** @var MarketStatus[] $info */
        $info = $this->repository->findAll();

        foreach ($info as $marketInfo) {

        }
    }
}
