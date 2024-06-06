<?php declare(strict_types = 1);

namespace App\Manager;

use App\Activity\ActivityTypes;
use App\Communications\CryptoRatesFetcherInterface;
use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\Activity\OrderEventActivity;
use App\Events\OrderEvent;
use App\Exchange\Config\MarketPairsConfig;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandler;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Market\Model\HighestPriceModel;
use App\Exchange\Order;
use App\Repository\MarketStatusRepository;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\BaseQuote;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Converter\RebrandingConverter;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Money\Money;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MarketStatusManager implements MarketStatusManagerInterface
{
    public const FILTER_DEPLOYED_ONLY_PREFIX = 'deployed_only_';

    public const FILTER_AIRDROP_ONLY = 'airdrop_only';
    public const FILTER_DEPLOYED_TOKEN = 'deployed';
    public const FILTER_USER_OWNS = 'user_owns';
    public const FILTER_NEWEST_DEPLOYED = 'newest_deployed';
    public const FILTER_AIRDROP_ACTIVE = true;

    public const SORT_LAST_PRICE = 'lastPrice';
    public const SORT_MONTH_VOLUME = 'monthVolume';
    public const SORT_DAY_VOLUME = 'dayVolume';
    public const SORT_CHANGE = 'change';
    public const SORT_PAIR = 'pair';
    public const SORT_BUY_DEPTH = 'buyDepth';
    public const SORT_MARKET_CAP = 'marketCap';
    public const SORT_MARKET_CAP_USD = 'marketCapUsd';
    public const SORT_RANK = 'rank';
    public const SORT_HOLDERS = 'holders';

    private MarketStatusRepository $repository;
    private MarketNameConverterInterface $marketNameConverter;
    private CryptoManagerInterface $cryptoManager;
    private MarketFactoryInterface $marketFactory;
    private MarketHandlerInterface $marketHandler;
    private EntityManagerInterface $em;
    private EventDispatcherInterface $eventDispatcher;
    private MoneyWrapperInterface $moneyWrapper;
    private CryptoRatesFetcherInterface $cryptoRatesFetcher;
    private ?QueryBuilder $queryBuilder;
    private MarketPairsConfig $marketPairsConfig;
    private HideFeaturesConfig $hideFeaturesConfig;
    private DisabledServicesConfig $disabledServicesConfig;
    private RebrandingConverterInterface $rebrandingConverter;
    public int $minVolumeForMarketcap;

    public static function buildDeployedOnlyFilter(string $symbol): string
    {
        $converter = new RebrandingConverter();

        $symbol = $converter->convert($symbol);

        return self::FILTER_DEPLOYED_ONLY_PREFIX . strtolower($symbol);
    }

    public function __construct(
        EntityManagerInterface $em,
        MarketStatusRepository $repository,
        MarketNameConverterInterface $marketNameConverter,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler,
        EventDispatcherInterface $eventDispatcher,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        MarketPairsConfig $marketPairsConfig,
        HideFeaturesConfig $hideFeaturesConfig,
        DisabledServicesConfig $disabledServicesConfig,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->em = $em;
        $this->repository = $repository;
        $this->marketNameConverter = $marketNameConverter;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->marketHandler = $marketHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->marketPairsConfig = $marketPairsConfig;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
        $this->disabledServicesConfig = $disabledServicesConfig;
        $this->rebrandingConverter = $rebrandingConverter;
    }

    public function getFilterForTokens(): array
    {
        $filters = [
            self::FILTER_AIRDROP_ONLY => self::FILTER_AIRDROP_ONLY,
            self::FILTER_USER_OWNS => self::FILTER_USER_OWNS,
            self::FILTER_NEWEST_DEPLOYED => self::FILTER_NEWEST_DEPLOYED,
        ];

        foreach ($this->disabledServicesConfig->getAllDeployBlockchains() as $symbol) {
            if (!$this->hideFeaturesConfig->isCryptoEnabled($symbol)) {
                continue;
            }

            $symbol = $this->rebrandingConverter->convert($symbol);

            $filter = self::buildDeployedOnlyFilter($symbol);
            $filters[$filter] = $filter;
        }

        return $filters;
    }

    public function getMarketsCount(string $filter = '', ?string $crypto = ''): int
    {
        $this->initQueryBuilder(self::FILTER_DEPLOYED_TOKEN);
        $this->setMarketsCrypto($crypto);
        $this->setMarketsInfoFilters([$filter]);

        $this->queryBuilder->select('COUNT(DISTINCT ms.quoteToken)');

        return (int)$this->queryBuilder->getQuery()->getSingleScalarResult();
    }

    /** {@inheritDoc} */
    public function getCryptoAndDeployedMarketsInfo(?int $offset = null, ?int $limit = null): array
    {
        return array_filter(
            $this->repository->getCryptoAndDeployedTokenMarketStatuses($offset, $limit),
            fn (MarketStatus $ms) => !$ms->getQuote() instanceof Crypto
                || $this->marketPairsConfig->isMarketPairEnabled(
                    $ms->getCrypto()->getSymbol(),
                    $ms->getQuote()->getSymbol()
                )
        );
    }

    /** {@inheritDoc} */
    public function createMarketStatus(array $markets): array
    {
        return array_map(function (Market $market) {
            $marketStatus = $this->repository->findByBaseQuoteNames(
                $market->getBase()->getSymbol(),
                $market->getQuote()->getSymbol()
            );

            if (!$marketStatus) {
                $crypto = $this->cryptoManager->findBySymbol($market->getBase()->getSymbol());

                if (!$crypto) {
                    throw new \Exception('Crypto not found ' . $market->getBase()->getSymbol());
                }

                $marketStatus = new MarketStatus($crypto, $market->getQuote());

                $this->em->persist($marketStatus);
                $this->em->flush();

                $this->updateMarketStatus($market);
            }

            return $marketStatus;
        }, $markets);
    }

    public function getOrCreateMarketStatus(Market $market): MarketStatus
    {
        return $this->getMarketStatus($market)
            ?? $this->createMarketStatus([$market])[0];
    }

    public function updateMarketStatusNetworks(Market $market): void
    {
        $marketStatus = $this->getOrCreateMarketStatus($market);
        $marketStatus->setNetworks($this->getTokenNetworks($marketStatus));

        $this->em->merge($marketStatus);
        $this->em->flush();
    }

    /** {@inheritDoc} */
    public function updateMarketStatus(Market $market): void
    {
        $marketStatus = $this->getOrCreateMarketStatus($market);

        $this->em->refresh($marketStatus);

        $marketInfo = $this->marketHandler->getMarketInfo(
            $market,
            MarketHandler::DAY_PERIOD
        );

        $marketStatus->updateStats($marketInfo);

        $orders = $this->marketHandler->getExecutedOrders($market, $marketStatus->getLastDealId());

        foreach ($orders as $order) {
            if (Order::DONATION_SIDE === $order->getSide()) {
                continue;
            }

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new OrderEventActivity($order, ActivityTypes::TOKEN_TRADED),
                OrderEvent::COMPLETED
            );
        }

        if (isset($orders[0])) {
            $marketStatus->setLastDealId($orders[0]->getId());
        }

        $marketStatus->setNetworks($this->getTokenNetworks($marketStatus));
        $marketStatus->setHolders($this->getHoldersCount($marketStatus));

        $this->em->merge($marketStatus);
        $this->em->flush();
    }

    private function getHoldersCount(MarketStatus $marketStatus): ?int
    {
        $quote = $marketStatus->getQuote();

        if ($quote instanceof Token) {
            return $quote->getHoldersCount();
        }

        return null;
    }

    private function getTokenNetworks(MarketStatus $marketStatus): ?array
    {
        $quote = $marketStatus->getQuote();

        if ($quote instanceof Token) {
            return array_reduce(
                $quote->getDeploys(),
                function ($networks, $deploy) {
                    if (!$deploy->isPending()) {
                        $networks[] = $deploy->getCrypto()->getSymbol();
                    }

                    return $networks;
                },
                []
            );
        }

        return null;
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

        $userMarketStatuses = $this->convertMarketStatusKeys(
            $this->repository->findBy(
                ['quoteToken' => $userTokenIds],
                ['lastPrice' => Criteria::DESC],
                $limit - count($predefinedMarketStatus),
                $offset
            )
        );

        return [
            'markets' =>
                array_merge(
                    $predefinedMarketStatus,
                    $userMarketStatuses
                ),
            'count' => count($markets),
        ];
    }

    /** {@inheritDoc} */
    public function getMarketStatus(Market $market): ?MarketStatus
    {
        return $this->repository->findByBaseQuoteNames(
            $market->getBase()->getSymbol(),
            $market->getQuote()->getSymbol()
        );
    }

    public function findByBaseQuoteNames(string $base, string $quote): ?MarketStatus
    {
        return $this->repository->findByBaseQuoteNames($base, $quote);
    }

    /** {@inheritDoc} */
    public function isValid(Market $market, bool $reverseBaseQuote = false): bool
    {
        if ($reverseBaseQuote) {
            $market = BaseQuote::reverseMarket($market);
        }

        return $this->isBaseValid($market) && $this->isQuoteValid($market)
            && (!$market->getQuote() instanceof Crypto || !$market->getBase() instanceof Crypto
                || $this->marketPairsConfig->isMarketPairEnabled(
                    $market->getQuote()->getSymbol(),
                    $market->getBase()->getSymbol(),
                )
            );
    }

    /** {@inheritDoc} */
    public function getExpired(): array
    {
        return $this->repository->getExpired();
    }

    /** {@inheritDoc} */
    public function getPredefinedMarketStatuses(): array
    {
        $predefinedMarkets =  array_filter(array_map(function (Market $market) {
            return $this->getOrCreateMarketStatus($market);
        }, $this->marketFactory->getCoinMarkets()));

        return $this->convertMarketStatusKeys($predefinedMarkets);
    }

    public function getFilteredPromotedMarketStatuses(): array
    {
        $this->initPromotedTokensQueryBuilder();
        // @TODO Use getArrayResult instead to avoid performance issues because of n+1 queries
        $marketsStatus = $this->queryBuilder->getQuery()->getResult();

        $marketsStatus = $this->convertMarketStatusKeys($marketsStatus);

        return $marketsStatus;
    }

    /** {@inheritDoc} */
    public function getFilteredMarketStatuses(
        int $offset,
        int $limit,
        string $sort = "monthVolume",
        string $order = "DESC",
        array $filters = [],
        ?int $userId = null,
        ?string $crypto = Symbols::WEB,
        ?string $searchPhrase = null
    ): array {
        $this->initQueryBuilder();
        $this->setMarketsCrypto($crypto);
        $this->setMarketsInfoFilters($filters, $userId, $searchPhrase);
        $this->setMarketsInfoOrder($sort, $order);
        $this->setOffsetAndLimit($offset, $limit);

        // @TODO Use getArrayResult instead to avoid performance issues because of n+1 queries
        $marketsStatus = $this->queryBuilder->getQuery()->getResult();

        $marketsStatus = $this->convertMarketStatusKeys($marketsStatus);

        return $marketsStatus;
    }

    /** @inheritDoc */
    public function getTokenHighestPrice(array $markets): HighestPriceModel
    {
        $marketStatuses = [];

        /** @var  array<Money> $lastPrices */
        $lastPrices = array_reduce($markets, function (array $acc, Market $market) use (&$marketStatuses) {
            $symbol = $market->getBase()->getSymbol();

            $marketStatuses[$symbol] = $this->getOrCreateMarketStatus($market);
            $lastPrice = $marketStatuses[$symbol]->getLastPrice();

            $acc[$symbol] = $lastPrice;

            return $acc;
        }, []);

        $cryptoRates = $this->cryptoRatesFetcher->fetch();

        $marketsStatusInUsd = [];

        array_walk(
            $lastPrices,
            function (Money $price, string $symbol) use (&$marketsStatusInUsd, $cryptoRates): void {
                $rate = $cryptoRates[$symbol][Symbols::USD];
                $marketsStatusInUsd[$symbol] = $this->moneyWrapper->format($price->multiply($rate));
            }
        );

        $highestLastPriceSymbol = array_search(
            max($marketsStatusInUsd),
            $marketsStatusInUsd,
        );

        $highestLastPriceSymbol = (string)$highestLastPriceSymbol ?: Symbols::WEB;
        $highestLastPriceSubunit = $markets[$highestLastPriceSymbol]->getQuote()->getShowSubunit();
        $highestLastPriceOpen = $this->moneyWrapper->format($marketStatuses[$highestLastPriceSymbol]->getOpenPrice());

        $highestLastPrice = isset($lastPrices[$highestLastPriceSymbol])
            ? $this->moneyWrapper->format($lastPrices[$highestLastPriceSymbol])
            : '0';

        return new HighestPriceModel(
            $highestLastPriceSymbol,
            $highestLastPrice,
            $highestLastPriceSubunit,
            $marketsStatusInUsd[$highestLastPriceSymbol] ?? '0',
            $highestLastPriceOpen,
        );
    }

    private function isBaseValid(Market $market): bool
    {
        $base = $market->getBase();
        $quote = $market->getQuote();

        return $base instanceof Token
            ? !$base->isBlocked() && $quote instanceof Crypto && $base->containsExchangeCrypto($quote)
            : $base instanceof Crypto && $base->isExchangeble();
    }

    private function isQuoteValid(Market $market): bool
    {
        $base = $market->getBase();
        $quote = $market->getQuote();

        return $base->getName() !== $quote->getName() || (
            $quote instanceof Crypto
                ? $quote->isTradable()
                : $quote instanceof Token
        );
    }

    private function initPromotedTokensQueryBuilder(): void
    {
        $this->queryBuilder = $this->repository->createQueryBuilder('ms')
            ->join('ms.quoteToken', 'qt')
            ->join('qt.promotions', 'tp', Join::WITH, 'tp.endDate > CURRENT_TIMESTAMP()')
            ->leftJoin('qt.deploys', 'dp')
            ->leftJoin('qt.rank', 'r')
            ->leftJoin('dp.crypto', 'c')
            ->addSelect(['qt', 'dp', 'c', 'r'])
            ->where('qt IS NOT NULL')
            ->andWhere('qt.isBlocked=false')
            ->orderBy('tp.createdAt', Criteria::ASC);
    }

    private function initQueryBuilder(?string $filter = null): void
    {
        $this->queryBuilder = $this->repository->createQueryBuilder('ms')
            ->join('ms.quoteToken', 'qt')
            ->join('qt.profile', 'p')
            ->leftJoin('qt.lockIn', 'li')
            ->leftJoin('qt.image', 'i')
            ->leftJoin('qt.discordConfig', 'disc')
            ->leftJoin('qt.deploys', 'dp')
            ->leftJoin('qt.rank', 'r')
            ->leftJoin('qt.signUpBonusCode', 'qtsb')
            ->leftJoin('dp.crypto', 'c')
            ->leftJoin('qt.promotions', 'tp', Join::WITH, 'tp.endDate > CURRENT_TIMESTAMP()')
            ->addSelect(['qt', 'msc','dp', 'c', 'p', 'li', 'i', 'disc', 'r', 'qtsb'])
            ->where('qt IS NOT NULL')
            ->andWhere('tp IS NULL')
            ->andWhere('qt.isBlocked=false')
            ->andWhere('dp.address IS NOT NULL');

        if (self::FILTER_DEPLOYED_TOKEN !== $filter) {
            $this->queryBuilder
                ->andWhere('qt.isHidden=false');
        }

        $deploySymbols = array_filter(
            $this->disabledServicesConfig->getAllDeployBlockchains(),
            fn($symbol) => $this->hideFeaturesConfig->isCryptoEnabled($symbol)
        );

        $this->queryBuilder
            ->andWhere($this->queryBuilder->expr()->in('c.symbol', $deploySymbols));
    }

    private function setOffsetAndLimit(int $offset, int $limit): void
    {
        $this->queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);
    }

    /**
     * @param array<string> $filters
     */
    private function setMarketsInfoFilters(
        array $filters,
        ?int $userId = null,
        ?string $searchPhrase = null
    ): void {
        if ($searchPhrase) {
            $this->queryBuilder
                ->andWhere("qt.deployed = true")
                ->andWhere('LOWER(qt.name) LIKE LOWER(:like)')
                ->setParameter('like', "%$searchPhrase%");

            return;
        }

        $this->addFiltersToQuery($filters, $userId);
    }

    private function getSymbolFromDeployedOnlyFilter(string $filter): ?string
    {
        if (str_starts_with($filter, self::FILTER_DEPLOYED_ONLY_PREFIX)) {
            return strtoupper(str_replace(self::FILTER_DEPLOYED_ONLY_PREFIX, '', $filter));
        }

        return null;
    }

    /**
     * @param array<string> $filters
     */
    private function addFiltersToQuery(array $filters, ?int $userId = null): void
    {
        $deployedBlockchains = [];

        foreach ($filters as $filter) {
            $symbol = $this->getSymbolFromDeployedOnlyFilter($filter);

            if ($symbol && $this->hideFeaturesConfig->isCryptoEnabled($symbol)) {
                $symbol = $this->rebrandingConverter->reverseConvert($symbol);

                $deployedBlockchains[] = $symbol;

                continue;
            }

            switch ($filter) {
                case self::FILTER_AIRDROP_ONLY:
                    $this->queryBuilder
                        ->innerJoin('qt.airdrops', 'a')
                        ->andWhere('a.status = :active')
                        ->setParameter('active', self::FILTER_AIRDROP_ACTIVE);

                    break;
                case self::FILTER_USER_OWNS:
                    $this->queryBuilder
                        ->leftJoin('qt.users', 'u')
                        ->andWhere('u.user = :id')
                        ->setParameter('id', $userId);

                    break;
                case self::FILTER_NEWEST_DEPLOYED:
                    $this->queryBuilder->addOrderBy(
                        'dp.deployDate',
                        Criteria::DESC
                    );

                    break;
            }
        }

        if (count($deployedBlockchains)) {
            $this->addDeployedFiltersToQuery($deployedBlockchains);
        }
    }

    private function addDeployedFiltersToQuery(array $blockchains = [Symbols::WEB]): void
    {
        $expressionBuilder = $this->em->getExpressionBuilder();

        $this->queryBuilder
            ->leftJoin('dp.crypto', 'dc')
            ->addSelect(['dc'])
            ->andWhere(
                $expressionBuilder->in(
                    'dc.symbol',
                    $blockchains
                )
            );
    }

    private function setMarketsInfoSort(string $sort): array
    {
        $result = [];

        switch ($sort) {
            case self::SORT_CHANGE:
                $result[] = 'ms.changePercentage';

                break;
            case self::SORT_MARKET_CAP:
                $result[] = 'ms.marketCap';

                break;
            case self::SORT_MARKET_CAP_USD:
                $this->queryBuilder
                     ->addSelect('
                        CASE WHEN ms.monthVolume >= :min_volume 
                        THEN ms.lastPrice 
                        ELSE 0 END 
                        AS HIDDEN min_volume_marketcap
                    ')
                     ->setParameter('min_volume', $this->minVolumeForMarketcap * 10000);

                $result[] = 'min_volume_marketcap';

                break;
            case self::SORT_LAST_PRICE:
                $result[] = 'ms.lastPrice';

                break;
            case self::SORT_DAY_VOLUME:
                $result[] = 'ms.dayVolume';

                break;
            case self::SORT_MONTH_VOLUME:
                $result[] = 'ms.monthVolume';

                break;
            case self::SORT_PAIR:
                $result[] = 'qt.name';

                break;
            case self::SORT_BUY_DEPTH:
                $result[] = 'ms.buyDepth';

                break;
            case self::SORT_RANK:
                $result[] = 'r.rank';

                break;
            case self::SORT_HOLDERS:
                $result[] = 'ms.holders';

                break;
            default:
                $result[] = 'r.rank';
        }

        $this->queryBuilder->groupBy('ms');

        return $result;
    }

    private function setMarketsInfoOrder(string $sort, string $order): void
    {
        $sortConfig = $this->setMarketsInfoSort($sort);
        $order = Criteria::ASC === $order
            ? Criteria::ASC
            : Criteria::DESC;

        foreach ($sortConfig as $sort) {
            $this->queryBuilder->addOrderBy($sort, $order);
        }

        $this->queryBuilder->addOrderBy('ms.id', $order);
    }

    private function setMarketsCrypto(?string $crypto = ''): void
    {
        if (!$crypto) {
            $this->queryBuilder->leftJoin('ms.crypto', 'msc')
                ->addSelect(['msc']);

            return;
        }

        $this->queryBuilder->leftJoin('ms.crypto', 'msc')
            ->andWhere('msc.symbol=:cryptoSymbol')
            ->addSelect(['msc'])
            ->setParameter('cryptoSymbol', strtoupper($crypto));
    }

    /**
     * @param array<MarketStatus|null> $marketStatuses
     * @return array<MarketStatus|null>
     */
    public function convertMarketStatusKeys(array $marketStatuses): array
    {
        $info = [];

        foreach (array_keys($marketStatuses) as $key) {
            $quote = $marketStatuses[$key]->getQuote();

            if (!$quote) {
                continue;
            }

            $market = $this->marketFactory->create($marketStatuses[$key]->getCrypto(), $quote);

            $info[$this->marketNameConverter->convert($market)] = $marketStatuses[$key];
        }

        return $info;
    }
}
