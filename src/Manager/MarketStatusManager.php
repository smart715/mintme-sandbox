<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Repository\MarketStatusRepository;
use App\Utils\Converter\MarketNameConverterInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

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

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        MarketNameConverterInterface $marketNameConverter,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler
    ) {
        $this->repository = $em->getRepository(MarketStatus::class);
        $this->marketNameConverter = $marketNameConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketFactory = $marketFactory;
        $this->marketHandler = $marketHandler;
        $this->em = $em;
    }

    public function getMarketsInfo(): array
    {
        $marketsInfo = [];

        /** @var MarketStatus[] $info */
        $info = $this->repository->findAll();

        foreach ($info as $marketInfo) {
            $quote = $marketInfo->getQuoteCrypto()
                ? $this->cryptoManager->findBySymbol($marketInfo->getQuoteCrypto()->getSymbol())
                : $this->tokenManager->findByName($marketInfo->getQuoteToken()->getName());

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

    public function createMarketStatus(array $markets): void
    {
        /** @var Market $market */
        foreach ($markets as $market) {
            $marketInfo = $this->marketHandler->getMarketInfo($market);
            $crypto = $this->cryptoManager->findBySymbol($market->getBase()->getSymbol());
            $quoteToken = $this->tokenManager->findByName($market->getQuote()->getName());
            $quouteCrypto = $this->cryptoManager->findBySymbol($market->getQuote()->getSymbol());

            if (!$crypto || !$quoteToken && !$quouteCrypto) {
                continue;
            }

            $this->em->persist(new MarketStatus($crypto, $quoteToken, $quouteCrypto, $marketInfo));
            $this->em->flush();
        }
    }

    public function updateMarketStatus(Market $market): void
    {
        $marketInfo = $this->marketHandler->getMarketInfo($market);
        $marketStatus = $this->repository->findByBaseQuoteNames(
            $market->getBase()->getSymbol(),
            $market->getQuote()->getSymbol() ?? $market->getQuote()->getName()
        );

        if (!$marketStatus) {
            throw new InvalidArgumentException();
        }

        $marketStatus->updateStats($marketInfo);

        $this->em->merge($marketStatus);
        $this->em->flush();
    }
}
