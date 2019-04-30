<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Repository\MarketStatusRepository;
use App\Utils\Converter\MarketNameConverterInterface;
use Doctrine\ORM\EntityManagerInterface;

class MarketStatusManager implements MarketStatusManagerInterface
{
    /** @var MarketStatusRepository */
    protected $repository;

    /** @var MarketNameConverterInterface */
    protected $marketNameConverter;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    public function __construct(
        EntityManagerInterface $em,
        MarketNameConverterInterface $marketNameConverter,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory
    ) {
        $this->repository = $em->getRepository(MarketStatus::class);
        $this->marketNameConverter = $marketNameConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketFactory = $marketFactory;
    }

    public function getMarketsInfo(): array
    {
        $marketsInfo = [];

        /** @var MarketStatus[] $info */
        $info = $this->repository->findAll();

        foreach ($info as $marketInfo) {
            $tokenName = $marketInfo->getTokenName();
            $quote = $this->cryptoManager->findBySymbol($tokenName) ?? $this->tokenManager->findByName($tokenName);

            if (!$quote) {
                continue;
            }

            $market = $this->marketFactory->create($marketInfo->getCrypto(), $quote);

            if (!$market) {
                continue;
            }

            $marketsInfo[$this->marketNameConverter->convert($market)] = $marketInfo;
        }

        return $marketsInfo;
    }
}
