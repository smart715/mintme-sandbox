<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\MarketInfo;
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

    public function getMarketsCount(): int
    {
        return $this->repository->count([]);
    }

    /** {@inheritDoc} */
    public function getMarketsInfo(int $offset, int $limit): array
    {
        return $this->parseMarketStatuses(
            $this->repository->findBy([], null, $limit, $offset)
        );
    }

    /** {@inheritDoc} */
    public function getAllMarketsInfo(): array
    {
        return $this->parseMarketStatuses(
            $this->repository->findAll()
        );
    }

    public function createMarketStatus(array $markets): void
    {
        /** @var Market $market */
        foreach ($markets as $market) {
            $marketStatus = $this->repository->findByBaseQuoteNames(
                $market->getBase()->getSymbol(),
                $market->getQuote()->getSymbol() ?? $market->getQuote()->getName()
            );

            if ($marketStatus) {
                continue;
            }

            $marketInfo = $this->marketHandler->getMarketInfo($market);
            $crypto = $this->cryptoManager->findBySymbol($market->getBase()->getSymbol());
            $quote = $this->tokenManager->findByName($market->getQuote()->getName())
                ?? $this->cryptoManager->findBySymbol($market->getQuote()->getSymbol());

            if (!$crypto || !$quote) {
                continue;
            }

            $this->em->persist(new MarketStatus($crypto, $quote, $marketInfo));
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

    /**
     * @param array<MarketStatus> $marketStatuses
     * @return array<MarketStatus>
     */
    private function parseMarketStatuses(array $marketStatuses): array
    {
        $info = [];

        foreach ($marketStatuses as $marketStatus) {
            $quote = $marketStatus->getQuote();

            if (!$quote) {
                continue;
            }

            $market = $this->marketFactory->create($marketStatus->getCrypto(), $quote);

            if (!$market) {
                continue;
            }

            $info[$this->marketNameConverter->convert($market)] = $marketStatus;
        }

        return $info;
    }
}
