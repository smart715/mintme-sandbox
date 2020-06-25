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
    private const SORTS = [
        'to_number(ms.lastPrice)',
        'to_number(ms.monthVolume)',
        'to_number(ms.dayVolume)',
        'change',
        'qt.name',
        'to_number(ms.buyDepth)',
        'marketcap(ms.lastPrice, ms.monthVolume, :minvolume)',
    ];

    private const SORTS_MAP = [
        'lastPrice' => 0,
        'lastPriceUSD' => 0,
        'monthVolume' => 1,
        'monthVolumeUSD' => 1,
        'dayVolume' => 2,
        'dayVolumeUSD' => 2,
        'change' => 3,
        'pair' => 4,
        'buyDepth' => 5,
        'buyDepthUSD' => 5,
        'marketCap' => 6,
        'marketCapUSD' => 6,
    ];

    private const SORT_BY_CHANGE = 'change';

    private const SORT_BY_MARKETCAP = ['marketCap', 'marketCapUSD'];

    private const DEPLOYED_FIRST = 1;
    private const DEPLOYED_ONLY = 2;

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

    /** @var int */
    public $minVolumeForMarketcap;

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

    public function getMarketsCount(int $deployed = 0): int
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(ms)')
            ->from(MarketStatus::class, 'ms');

        if (self::DEPLOYED_ONLY === $deployed) {
            $qb->join('ms.quoteToken', 'qt')
                ->where("qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x'");
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function getUserRelatedMarketsCount(int $userId): int
    {
        return (int)$this->em->createQueryBuilder()
            ->select('COUNT(ms)')
            ->from(MarketStatus::class, 'ms')
            ->join('ms.quoteToken', 'qt')
            ->innerJoin('qt.users', 'u', 'WITH', 'u.user = :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** {@inheritDoc} */
    public function getMarketsInfo(
        int $page,
        int $offset,
        string $sort = "monthVolume",
        string $order = "DESC",
        int $deployed = 0,
        ?int $userId = null
    ): array {
        $predefinedMarketStatus = $this->getPredefinedMarketStatuses();

        $firstResult = ($offset - count($predefinedMarketStatus)) * ($page - 1);

        $queryBuilder = $this->repository->createQueryBuilder('ms')
            ->join('ms.quoteToken', 'qt')
            ->where('qt IS NOT NULL')
            ->setFirstResult($firstResult)
            ->setMaxResults($offset - count($predefinedMarketStatus));

        if (null !== $userId) {
            $queryBuilder->innerJoin('qt.users', 'u', 'WITH', 'u.user = :id')
                ->setParameter('id', $userId);
        }

        if (self::DEPLOYED_FIRST === $deployed) {
            $queryBuilder->addSelect(
                "CASE WHEN qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x' THEN 1 ELSE 0 END AS HIDDEN deployed"
            )
            ->orderBy('deployed', 'DESC');
        } elseif (self::DEPLOYED_ONLY === $deployed) {
            $queryBuilder->andWhere("qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x'");
        }

        if (self::SORT_BY_CHANGE === $sort) {
            $queryBuilder->addSelect('change_percentage(ms.lastPrice, ms.openPrice) AS HIDDEN change');
        }

        if (in_array($sort, self::SORT_BY_MARKETCAP)) {
            $queryBuilder->setParameter('minvolume', $this->minVolumeForMarketcap * 10000);
        }

        $sort = isset(self::SORTS_MAP[$sort])
            ? self::SORTS[self::SORTS_MAP[$sort]]
            : self::SORTS[self::SORTS_MAP['monthVolume']];
        $order = 'ASC' === $order
            ? 'ASC'
            : 'DESC';

        $queryBuilder->addOrderBy($sort, $order)
            ->addOrderBy('ms.id', $order);
        
        return $this->parseMarketStatuses(
            array_merge(
                $predefinedMarketStatus,
                $queryBuilder->getQuery()->getResult()
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
            if ($market->getQuote() instanceof Token) {
                array_push($userTokenIds, $market->getQuote()->getId());
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
