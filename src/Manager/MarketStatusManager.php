<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandler;
use App\Exchange\Market\MarketHandlerInterface;
use App\Repository\MarketStatusRepository;
use App\Utils\BaseQuote;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MarketStatusManager implements MarketStatusManagerInterface
{
    public const FILTER_DEPLOYED_FIRST = 1;
    public const FILTER_DEPLOYED_ONLY_MINTME = 2;
    public const FILTER_AIRDROP_ONLY = 3;
    public const FILTER_AIRDROP_ACTIVE = true;

    public const SORT_LAST_PRICE = 'lastPrice';
    public const SORT_MONTH_VOLUME = 'monthVolume';
    public const SORT_DAY_VOLUME = 'dayVolume';
    public const SORT_CHANGE = 'change';
    public const SORT_PAIR = 'pair';
    public const SORT_BUY_DEPTH = 'buyDepth';
    public const SORT_MARKET_CAP = 'marketCap';
    public const SORT_MARKET_CAP_USD = 'marketCapUsd';

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
        $queryBuilder = $this->repository->createQueryBuilder('ms')
            ->select('COUNT(ms)')
            ->join('ms.quoteToken', 'qt')
            ->leftJoin('qt.crypto', 'c')
            ->where('qt IS NOT NULL')
            ->andWhere('qt.isBlocked=false');

        switch ($filter) {
            case self::FILTER_DEPLOYED_ONLY_MINTME:
                $queryBuilder->andWhere(
                    "qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x' AND (qt.crypto IS NULL OR c.symbol = :cryptoSymbol)"
                )->setParameter('cryptoSymbol', Token::WEB_SYMBOL);

                break;
            case self::FILTER_AIRDROP_ONLY:
                $queryBuilder->innerJoin('qt.airdrops', 'a')
                    ->andWhere('a.status = :active')
                    ->setParameter('active', self::FILTER_AIRDROP_ACTIVE);

                break;
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getMarketsInfoFilter(int $filter, QueryBuilder $queryBuilder): void
    {
        switch ($filter) {
            case self::FILTER_DEPLOYED_FIRST:
                $queryBuilder->addSelect(
                    "CASE WHEN qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x' THEN 1 ELSE 0 END AS HIDDEN deployed"
                )->addOrderBy('deployed', 'DESC');

                break;
            case self::FILTER_DEPLOYED_ONLY_MINTME:
                $queryBuilder->andWhere(
                    "qt.address IS NOT NULL AND qt.address != '' AND qt.address != '0x' AND (qt.crypto IS NULL OR c.symbol = :cryptoSymbol)"
                )->setParameter('cryptoSymbol', Token::WEB_SYMBOL);

                break;
            case self::FILTER_AIRDROP_ONLY:
                $queryBuilder->innerJoin('qt.airdrops', 'a')
                    ->andWhere('a.status = :active')
                    ->setParameter('active', self::FILTER_AIRDROP_ACTIVE);

                break;
        }
    }

    private function getMarketsInfoSort(string $sort, QueryBuilder $queryBuilder): string
    {
        switch ($sort) {
            case self::SORT_CHANGE:
                $queryBuilder->addSelect('change_percentage(ms.lastPrice, ms.openPrice) AS HIDDEN change');
                $sort = 'change';

                break;
            case self::SORT_MARKET_CAP:
            case self::SORT_MARKET_CAP_USD:
                $queryBuilder->setParameter('minvolume', $this->minVolumeForMarketcap * 10000);
                $sort = 'marketcap(ms.lastPrice, ms.monthVolume, :minvolume)';

                break;
            case self::SORT_LAST_PRICE:
                $sort = 'to_number(ms.lastPrice)';

                break;
            case self::SORT_DAY_VOLUME:
                $sort = 'to_number(ms.dayVolume)';

                break;
            case self::SORT_PAIR:
                $sort = 'qt.name';

                break;
            case self::SORT_BUY_DEPTH:
                $sort = 'to_number(ms.buyDepth)';

                break;
            default:
                $sort = 'to_number(ms.monthVolume)';
        }

        return $sort;
    }

    private function getMarketsInfoOrder(string $sort, string $order, QueryBuilder $queryBuilder): void
    {
        $order = 'ASC' === $order
            ? 'ASC'
            : 'DESC';

        $queryBuilder->addOrderBy($sort, $order)
            ->addOrderBy('qt.crypto', 'ASC')
            ->addOrderBy('ms.id', $order);
    }


    /** {@inheritDoc} */
    public function getUserRelatedMarketsCount(int $userId): int
    {
        return (int)$this->repository->createQueryBuilder()
            ->select('COUNT(ms)')
            ->join('ms.quoteToken', 'qt')
            ->innerJoin('qt.users', 'u', 'WITH', 'u.user = :id')
            ->where('qt IS NOT NULL')
            ->andWhere('qt.isBlocked=false')
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
            ->orderBy('qt.crypto', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if (null !== $userId) {
            $queryBuilder->innerJoin('qt.users', 'u', 'WITH', 'u.user = :id')
                ->setParameter('id', $userId);
        }

        $this->getMarketsInfoFilter($filter, $queryBuilder);
        $sort = $this->getMarketsInfoSort($sort, $queryBuilder);
        $this->getMarketsInfoOrder($sort, $order, $queryBuilder);

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

        $marketInfo = $this->marketHandler->getMarketInfo(
            $market,
            MarketHandler::DAY_PERIOD
        );

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
