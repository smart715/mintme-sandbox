<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Repository\MarketStatusRepository;
use App\Utils\BaseQuote;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class MarketStatusManager implements MarketStatusManagerInterface
{
    private const SORTS = [
        'lastPrice' => 'to_number(ms.lastPrice)',
        'monthVolume' => 'to_number(ms.monthVolume)',
        'dayVolume' => 'to_number(ms.dayVolume)',
        'change' => 'change',
        'pair' => 'qt.name',
        'buyDepth' => 'to_number(ms.buyDepth)',
        'marketCap' => 'marketcap(ms.lastPrice, ms.monthVolume, :minvolume)',
    ];

    private const SORT_BY_CHANGE = 'change';

    private const SORT_BY_MARKETCAP = ['marketCap', 'marketCapUSD'];

    private const DEPLOYED_FIRST = 1;
    private const DEPLOYED_ONLY = 2;
    private const AIRDROP_ONLY = 3;
    private const AIRDROP_ACTIVE = 1;

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

    private BalanceHandlerInterface $balanceHandler;

    private ParameterBagInterface $bag;

    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        EntityManagerInterface $em,
        MarketNameConverterInterface $marketNameConverter,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler,
        BalanceHandlerInterface $balanceHandler,
        ParameterBagInterface $bag,
        MoneyWrapperInterface $moneyWrapper
    ) {
        /** @var  MarketStatusRepository $repository */
        $repository = $em->getRepository(MarketStatus::class);
        $this->repository = $repository;
        $this->marketNameConverter = $marketNameConverter;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->marketHandler = $marketHandler;
        $this->em = $em;
        $this->balanceHandler = $balanceHandler;
        $this->bag = $bag;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** {@inheritDoc} */
    public function getMarketsCount(int $filter = 0): int
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(ms)')
            ->from(MarketStatus::class, 'ms')
            ->join('ms.quoteToken', 'qt');

        if (self::DEPLOYED_ONLY === $filter) {
            $qb->where("qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x'");
        } elseif (self::AIRDROP_ONLY === $filter) {
            $qb->innerJoin('qt.airdrops', 'a')
                ->where('a.status = :active')
                ->setParameter('active', self::AIRDROP_ACTIVE);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /** {@inheritDoc} */
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
        int $offset,
        int $limit,
        string $sort = "monthVolume",
        string $order = "DESC",
        int $filter = 1,
        ?int $userId = null
    ): array {
        $predefinedMarketStatus = $this->getPredefinedMarketStatuses();

        $queryBuilder = $this->repository->createQueryBuilder('ms')
            ->join('ms.quoteToken', 'qt')
            ->leftJoin('qt.crypto', 'c')
            ->where('qt IS NOT NULL')
            ->andWhere('qt.isBlocked=false')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if (null !== $userId) {
            $queryBuilder->innerJoin('qt.users', 'u', 'WITH', 'u.user = :id')
                ->setParameter('id', $userId);
        }

        if (self::DEPLOYED_FIRST === $filter) {
            $queryBuilder->addSelect(
                "CASE WHEN qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x' THEN 1 ELSE 0 END AS HIDDEN deployed"
            )
            ->orderBy('deployed', 'DESC');
        } elseif (self::DEPLOYED_ONLY === $filter) {
            $queryBuilder->andWhere(
                "qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x' AND (qt.crypto IS NULL OR c.symbol = :cryptoSymbol)"
            )->setParameter('cryptoSymbol', Token::WEB_SYMBOL);
        } elseif (self::AIRDROP_ONLY === $filter) {
            $queryBuilder->innerJoin('qt.airdrops', 'a')
                ->andWhere('a.status = :active')
                ->setParameter('active', self::AIRDROP_ACTIVE);
        }

        if (self::SORT_BY_CHANGE === $sort) {
            $queryBuilder->addSelect('change_percentage(ms.lastPrice, ms.openPrice) AS HIDDEN change');
        }

        if (in_array($sort, self::SORT_BY_MARKETCAP)) {
            $queryBuilder->setParameter('minvolume', $this->minVolumeForMarketcap * 10000);
        }

        $sort = self::SORTS[$sort] ?? self::SORTS['monthVolume'];
        $order = 'ASC' === $order
            ? 'ASC'
            : 'DESC';

        $queryBuilder->addOrderBy($sort, $order)
            ->addOrderBy('qt.crypto', 'ASC')
            ->addOrderBy('ms.id', $order);

        return $this->parseMarketStatuses(
            array_merge(
                $predefinedMarketStatus,
                $queryBuilder->getQuery()->getResult()
            )
        );
    }

    /** {@inheritDoc} */
    public function getCryptoAndDeployedMarketsInfo(): array
    {
        return $this->repository->getCryptoAndDeployedTokenMarketStatuses();
    }

    /** {@inheritDoc} */
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

    /** {@inheritDoc} */
    public function updateMarketStatus(Market $market): void
    {
        $marketStatus = $this->repository->findByBaseQuoteNames(
            $market->getBase()->getSymbol(),
            $market->getQuote()->getSymbol()
        );

        if (!$marketStatus) {
            throw new InvalidArgumentException(
                "Nonexistent market: {$market->getBase()->getSymbol()}/{$market->getQuote()->getSymbol()}"
            );
        }

        $marketInfo = $this->marketHandler->getMarketInfo($market);

        /** @var Token|null $quote */
        $quote = $market->getQuote();

        $marketCap = null;

        if ($quote instanceof Token && $quote->isMintmeToken()) {
            $ownerPendingOrders = $this->marketHandler->getPendingOrdersByUser(
                $quote->getProfile()->getUser(),
                [$market]
            );

            $soldOnMarket = $this->balanceHandler->soldOnMarket(
                $quote,
                $this->bag->get('token_quantity'),
                $ownerPendingOrders
            );

            $marketCap = $marketInfo->getLast()->multiply($this->moneyWrapper->format($soldOnMarket));
        }

        $marketStatus->updateStats($marketInfo, $marketCap);

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

    /**
     * @retrun array<MarketStatus>|null
     */
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
     * @return array
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

            $info[$this->marketNameConverter->convert($market)] = $marketStatus;
        }

        return $info;
    }

    /** {@inheritDoc} */
    public function getMarketStatus(Market $market): ?MarketStatus
    {
        return $this->repository->findByBaseQuoteNames(
            $market->getBase()->getSymbol(),
            $market->getQuote()->getSymbol()
        );
    }

    /** {@inheritDoc} */
    public function isValid(Market $market, bool $reverseBaseQuote = false): bool
    {
        if ($reverseBaseQuote) {
            $market = BaseQuote::reverseMarket($market);
        }

        $base = $market->getBase();
        $quote = $market->getQuote();

        return
            !(
                ($base instanceof Crypto && !$base->isExchangeble()) ||
                ($quote instanceof Crypto && !$quote->isTradable()) ||
                ($base instanceof Token && $base->isBlocked()) ||
                $quote instanceof Token ||
                $base === $quote ||
                ($base instanceof Token && !(Token::MINTME_SYMBOL === $quote->getSymbol() || Token::WEB_SYMBOL === $quote->getSymbol()))
            );
    }

    /** {@inheritDoc} */
    public function getExpired(): array
    {
        return $this->repository->getExpired();
    }
}
