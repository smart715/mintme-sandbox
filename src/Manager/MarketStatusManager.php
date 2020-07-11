<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\MarketInfo;
use App\Repository\MarketStatusRepository;
use App\Utils\Converter\MarketNameConverterInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class MarketStatusManager implements MarketStatusManagerInterface
{
    /** @var MarketStatusRepository */
    protected $repository;

    /** @var MarketNameConverterInterface */
    protected $marketNameConverter;

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
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler
    ) {
        /** @var  MarketStatusRepository $repository */
        $repository = $em->getRepository(MarketStatus::class);
        $this->repository = $repository;
        $this->marketNameConverter = $marketNameConverter;
        $this->cryptoManager = $cryptoManager;
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
        $predefinedMarketStatus = $this->getPredefinedMarketStatuses();

        return $this->parseMarketStatuses(
            array_merge(
                $predefinedMarketStatus,
                $this->repository->createQueryBuilder('ms')
                    ->addSelect("CASE WHEN qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x' THEN 1 ELSE 0 END AS HIDDEN deployed")
                    ->join('ms.quoteToken', 'qt')
                    ->where('qt IS NOT NULL')
                    ->andWhere('qt.isBlocked=false')
                    ->orderBy('deployed', 'DESC')
                    ->addOrderBy('ms.lastPrice', 'DESC')
                    ->setFirstResult($offset)
                    ->setMaxResults($limit - count($predefinedMarketStatus))
                    ->getQuery()
                    ->getResult()
            )
        );
    }

    /** {@inheritDoc} */
    public function getAllMarketsInfo(): array
    {
        return $this->parseMarketStatuses(
            $this->repository->findBy([], ['lastPrice' => Criteria::DESC])
        );
    }

    public function createMarketStatus(array $markets): void
    {
        /** @var Market $market */
        foreach ($markets as $market) {
            $marketStatus = $this->repository->findByBaseQuoteNames(
                $market->getBase()->getSymbol(),
                $market->getQuote()->getSymbol()
            );

            if ($marketStatus) {
                continue;
            }

            $marketInfo = $this->marketHandler->getMarketInfo($market);
            $crypto = $this->cryptoManager->findBySymbol($market->getBase()->getSymbol());

            if (!$crypto) {
                continue;
            }

            $this->em->persist(new MarketStatus($crypto, $market->getQuote(), $marketInfo));
            $this->em->flush();
        }
    }

    public function updateMarketStatus(Market $market): void
    {
        $marketInfo = $this->marketHandler->getMarketInfo($market);
        $marketStatus = $this->repository->findByBaseQuoteNames(
            $market->getBase()->getSymbol(),
            $market->getQuote()->getSymbol()
        );

        if (!$marketStatus) {
            throw new InvalidArgumentException(
                "Nonexistent market: {$market->getBase()->getSymbol()}/{$market->getQuote()->getSymbol()}"
            );
        }

        $marketStatus->updateStats($marketInfo);

        $this->em->merge($marketStatus);
        $this->em->flush();
    }

    /** {@inheritDoc} */
    public function getUserMarketStatus(User $user, int $offset, int $limit, bool $deployed = false): array
    {
        $userTokenIds = [];
        $predefinedMarketStatus = $this->getPredefinedMarketStatuses();
        $markets = $this->marketFactory->createUserRelated($user, $deployed);

        foreach ($markets as $market) {
            /** @var Token $token */
            $token = $market->getQuote();

            if ($token instanceof Token && !$token->isBlocked()) {
                array_push($userTokenIds, $token->getId());
            }
        }

        return [
            'markets' => $this->parseMarketStatuses(
                array_merge(
                    $predefinedMarketStatus,
                    $this->repository->findBy(
                        ['quoteToken' => $userTokenIds],
                        ['lastPrice' => Criteria::DESC],
                        $limit - count($predefinedMarketStatus),
                        $offset
                    )
                )
            ),
            'count' => count($markets),
        ];
    }

    private function getPredefinedMarketStatuses(): array
    {
        return array_map(function (Market $market) {
            return $this->repository->findByBaseQuoteNames(
                $market->getBase()->getSymbol(),
                $market->getQuote()->getSymbol()
            );
        }, $this->marketFactory->getCoinMarkets());
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

    /**
     * Return market status
     *
     * @param Market $market
     * @return MarketStatus|null
     */
    public function getMarketStatus(Market $market): ?MarketStatus
    {
        return $this->repository->findByBaseQuoteNames(
            $market->getBase()->getSymbol(),
            $market->getQuote()->getSymbol()
        );
    }
}
