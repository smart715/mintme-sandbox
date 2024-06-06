<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Deal;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Factory\MarketSummaryFactory;
use App\Exchange\Market;
use App\Exchange\Market\Model\BuyOrdersSummaryResult;
use App\Exchange\Market\Model\LineStat;
use App\Exchange\Market\Model\SellOrdersSummaryResult;
use App\Exchange\Market\Model\Summary;
use App\Exchange\MarketInfo;
use App\Exchange\Order;
use App\Exchange\Trade\CheckTradeResult;
use App\Manager\CryptoManagerInterface;
use App\Manager\DonationManagerInterface;
use App\Manager\UserManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\AssetType;
use App\Utils\BaseQuote;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Model\Transaction;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use DateInterval;
use Doctrine\ORM\PersistentCollection;
use Exception;
use InvalidArgumentException;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MarketHandler implements MarketHandlerInterface
{
    public const SELL = 1;
    public const BUY = 2;
    public const DAY_PERIOD = 86400;
    public const MONTH_PERIOD = 2592000;
    public const PERIOD_TYPE_MONTH = 'month';
    public const PERIOD_TYPE_WEEK = 'week';
    public const PERIOD_TYPE_HALF_YEAR = 'half_year';

    private MarketFetcherInterface $marketFetcher;
    private MoneyWrapperInterface $moneyWrapper;
    private UserManagerInterface $userManager;
    private MarketNameConverterInterface $marketNameConverter;
    private DonationManagerInterface $donationManager;
    private MarketFactoryInterface $marketFactory;
    private CryptoManagerInterface $cryptoManager;
    private BalanceHandlerInterface $balanceHandler;
    private ContractHandlerInterface $contractHandler;
    private ParameterBagInterface $parameterBag;
    private LimitHistoryConfig $limitHistoryConfig;

    public function __construct(
        MarketFetcherInterface $marketFetcher,
        MoneyWrapperInterface $moneyWrapper,
        UserManagerInterface $userManager,
        MarketNameConverterInterface $marketNameConverter,
        DonationManagerInterface $donationManager,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler,
        ContractHandlerInterface $contractHandler,
        ParameterBagInterface $parameterBag,
        LimitHistoryConfig $limitHistoryConfig
    ) {
        $this->marketFetcher = $marketFetcher;
        $this->moneyWrapper = $moneyWrapper;
        $this->userManager = $userManager;
        $this->marketNameConverter = $marketNameConverter;
        $this->donationManager = $donationManager;
        $this->marketFactory = $marketFactory;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->parameterBag = $parameterBag;
        $this->contractHandler = $contractHandler;
        $this->limitHistoryConfig = $limitHistoryConfig;
    }

    /** {@inheritdoc} */
    public function getExecutedOrder(Market $market, int $id, int $limit = 100): ?Order
    {
        $orders = $this->getExecutedOrders($market, 0, $limit);

        foreach ($orders as $order) {
            if ($order->getId() === $id) {
                return $order;
            }
        }

        return null;
    }

    public function getPendingOrder(Market $market, int $id): ?Order
    {
        $order = $this->marketFetcher->getPendingOrder(
            $this->marketNameConverter->convert($market),
            $id
        );

        return $this->parsePendingOrders([$order], $market)[0] ?? null;
    }

    /** {@inheritdoc} */
    public function getPendingSellOrders(
        Market $market,
        int $offset = 0,
        int $limit = 100,
        bool $reverseBaseQuote = false
    ): array {
        return $this->parsePendingOrders(
            $this->getPendingOrders($market, $offset, $limit, self::SELL),
            $market,
            $reverseBaseQuote
        );
    }

    /**
     * @return Order[]
     */
    private function getAllPendingOrders(Market $market, int $side): array
    {
        $offset = 0;
        $limit = 100;
        $orders = [];

        do {
            $moreOrders = self::BUY === $side
                ? $this->getPendingBuyOrders($market, $offset, $limit)
                : $this->getPendingSellOrders($market, $offset, $limit);

            foreach ($moreOrders as $order) {
                $id = $order->getId();

                // In case of duplicates or order updated while looping
                unset($orders[$id]);
                $orders[$id] = $order;
            }

            $offset += $limit;
        } while (count($moreOrders) >= $limit);

        return $orders;
    }

    public function getAllPendingBuyOrders(Market $market): array
    {
        return $this->getAllPendingOrders($market, self::BUY);
    }

    public function getAllPendingSellOrders(Market $market): array
    {
        return $this->getAllPendingOrders($market, self::SELL);
    }

    /** {@inheritdoc} */
    public function getPendingBuyOrders(
        Market $market,
        int $offset = 0,
        int $limit = 100,
        bool $reverseBaseQuote = false
    ): array {
        return $this->parsePendingOrders(
            $this->getPendingOrders($market, $offset, $limit, self::BUY),
            $market,
            $reverseBaseQuote
        );
    }

    /** {@inheritdoc} */
    public function getExecutedOrders(
        Market $market,
        int $lastId = 0,
        int $limit = 100,
        bool $reverseBaseQuote = false
    ): array {
        return $this->parseExecutedOrders(
            $this->marketFetcher->getExecutedOrders(
                $this->marketNameConverter->convert($market),
                $lastId,
                $limit
            ),
            $market,
            $reverseBaseQuote
        );
    }

    /** {@inheritdoc} */
    public function getUserExecutedHistory(
        User $user,
        array $markets,
        int $offset = 0,
        int $limit = 100,
        bool $reverseBaseQuote = false,
        int $donationsOffset = 0,
        int $fullDonationsOffset = 0
    ): array {
        if (count($markets) > 1000) {
            $userExecutedHistory = $this->batchGetUserHistory($user, $markets, $offset, $limit, $fullDonationsOffset);
        } else {
            $marketsStr = implode(
                ',',
                array_map(fn(Market $market) => $this->marketNameConverter->convert($market), $markets)
            );

            $userExecutedHistory = $this->marketFetcher->getUserExecutedHistory(
                $user->getId(),
                $marketsStr,
                $offset - $fullDonationsOffset,
                $limit
            );
        }

        $marketDeals = $this->parseDeals(
            $userExecutedHistory,
            $markets,
            $reverseBaseQuote
        );

        $donations = $this->donationManager->getUserRelated($user, $donationsOffset, $limit);

        $deals = array_merge(
            $marketDeals,
            $this->donationsToDeals($donations, $user)
        );

        uasort($deals, static function (Deal $lDeal, Deal $rDeal) {
            return $rDeal->getTimestamp() - $lDeal->getTimestamp();
        });

        return array_slice($deals, 0, $limit);
    }

    private function batchGetUserHistory(User $user, array $markets, int $offset, int $limit, int $fullDonationsOffset): array
    {
        $marketHistory = [];
        $marketsChunks = array_chunk($markets, 1000);

        foreach ($marketsChunks as $marketsChunk) {
            $marketsStr = implode(
                ',',
                array_map(fn(Market $market) => $this->marketNameConverter->convert($market), $marketsChunk)
            );

            $userExecutedHistory = $this->marketFetcher->getUserExecutedHistory(
                $user->getId(),
                $marketsStr,
                $offset - $fullDonationsOffset,
                $limit
            );

            $marketHistory = array_merge($marketHistory, $userExecutedHistory);
        }

        return $marketHistory;
    }

    /** {@inheritdoc} */
    public function getPendingOrdersByUser(
        User $user,
        array $markets,
        int $offset = 0,
        int $limit = 100,
        bool $reverseBaseQuote = false
    ): array {
        $marketOrders = array_map(function (Market $market) use ($user, $offset, $limit, $reverseBaseQuote) {
            $ordersLimit = $offset + $limit;
            $rawOrders = [];
            $totalRequested = 0;

            do {
                $internalLimit = min($ordersLimit, 100);

                $result = $this->marketFetcher->getPendingOrdersByUser(
                    $user->getId(),
                    $this->marketNameConverter->convert($market),
                    $totalRequested,
                    $internalLimit
                );

                if (0 === count($result)) {
                    break;
                }

                $totalRequested += $internalLimit;
                $ordersLimit -= $internalLimit;

                $rawOrders = array_merge($rawOrders, $result);
            } while ($ordersLimit > 0);

            return $this->parsePendingOrders(
                $rawOrders,
                $market,
                $reverseBaseQuote
            );
        }, $markets);

        $orders = $marketOrders ? array_merge(...$marketOrders) : [];

        uasort($orders, static function (Order $lOrder, Order $rOrder) {
            return $lOrder->getTimestamp() - $rOrder->getTimestamp();
        });

        return array_slice($orders, $offset, $limit);
    }

    public function getExpectedSellResult(Market $market, string $amount, string $feeRate): CheckTradeResult
    {
        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getSymbol();

        $quoteSymbol = $quote->getMoneySymbol();

        $amountToReceive = $this->moneyWrapper->parse('0', $baseSymbol);

        $quoteLeft = $this->moneyWrapper->parse($amount, $quoteSymbol);

        $offset = 0;
        $limit = 100;

        do {
            $pendingBuyOrders = $this->getPendingBuyOrders($market, $offset, $limit);
            $shouldContinue = count($pendingBuyOrders) >= $limit;
            $offset += $limit;

            foreach ($pendingBuyOrders as $bid) {
                if ($quoteLeft->isZero()) {
                    $shouldContinue = false;

                    break;
                }

                if ($quoteLeft->greaterThanOrEqual($bid->getAmount())) {
                    $baseWorth = $this->moneyWrapper->convertByRatio(
                        $bid->getAmount(),
                        $baseSymbol,
                        $this->moneyWrapper->format($bid->getPrice())
                    );

                    $amountToReceive = $amountToReceive->add($baseWorth);
                    $quoteLeft = $quoteLeft->subtract($bid->getAmount());
                } else {
                    $baseWorth = $this->moneyWrapper->convertByRatio(
                        $quoteLeft,
                        $baseSymbol,
                        $this->moneyWrapper->format($bid->getPrice())
                    );

                    $amountToReceive = $amountToReceive->add($baseWorth);
                    $quoteLeft = $quoteLeft->subtract($quoteLeft);
                }
            }
        } while ($shouldContinue);

        if (!$amountToReceive->isZero()) {
            $amountToReceive = $amountToReceive->subtract($amountToReceive->multiply($feeRate));
        }

        return new CheckTradeResult($amountToReceive);
    }

    public function getExpectedSellReversedResult(
        Market $market,
        string $amountToReceive,
        string $feeRate
    ): CheckTradeResult {
        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getMoneySymbol();
        $quoteSymbol = $quote->getMoneySymbol();

        $baseLeft = $this->moneyWrapper->parse($amountToReceive, $baseSymbol);

        $one = $this->moneyWrapper->parse('1', $baseSymbol);
        $fee = $this->moneyWrapper->parse($feeRate, $baseSymbol);
        $oneMinusFee = $one->subtract($fee);

        $amount = $this->moneyWrapper->parse('0', $quoteSymbol);
        $denominator = $this->moneyWrapper->format($oneMinusFee);
        $baseLeft = $baseLeft->divide($denominator);

        $offset = 0;
        $limit = 100;

        do {
            $pendingBuyOrders = $this->getPendingBuyOrders($market, $offset, $limit);
            $shouldContinue = count($pendingBuyOrders) >= $limit;
            $offset += $limit;

            foreach ($pendingBuyOrders as $bid) {
                if ($baseLeft->isZero()) {
                    $shouldContinue = false;

                    break;
                }

                $price = $bid->getPrice();
                $worth = $this->moneyWrapper->convertByRatio(
                    $bid->getAmount(),
                    $baseSymbol,
                    $this->moneyWrapper->format($price)
                );

                if ($baseLeft->greaterThanOrEqual($worth)) {
                    $amount = $amount->add($bid->getAmount());
                    $baseLeft = $baseLeft->subtract($worth);
                } else {
                    $amountToAdd = $bid->getAmount()
                        ->multiply($this->moneyWrapper->format($baseLeft))
                        ->divide($this->moneyWrapper->format($worth));
                    $amount = $amount->add($amountToAdd);
                    $baseLeft = $baseLeft->subtract($baseLeft);
                }
            }
        } while ($shouldContinue);

        return new CheckTradeResult($amount, $baseLeft);
    }

    public function getExpectedBuyResult(Market $market, string $amount, string $feeRate): CheckTradeResult
    {
        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getSymbol();

        $quoteSymbol = $quote->getSymbol();

        $amountToReceive = $this->moneyWrapper->parse('0', $quoteSymbol);
        $worth = $this->moneyWrapper->parse('0', $baseSymbol);

        $baseLeft = $this->moneyWrapper->parse($amount, $baseSymbol);

        if ($market->isTokenMarket() && !$baseLeft->isZero()) {
            // When buying tokens, the fee is taken from the amount that the user spends (MINTME)
            $baseLeft =  $baseLeft->subtract($baseLeft->multiply($feeRate));
        }

        $offset = 0;
        $limit = 100;

        do {
            $pendingSellOrders = $this->getPendingSellOrders($market, $offset, $limit);
            $shouldContinue = count($pendingSellOrders) >= $limit;
            $offset += $limit;

            foreach ($pendingSellOrders as $ask) {
                if ($baseLeft->isZero()) {
                    $shouldContinue = false;

                    break;
                }

                $orderTotalPrice = $ask->getPrice()->multiply(
                    $this->moneyWrapper->format($ask->getAmount())
                );

                if ($baseLeft->greaterThanOrEqual($orderTotalPrice)) {
                    $quoteWorth = $ask->getAmount();

                    $amountToReceive = $amountToReceive->add($quoteWorth);
                    $worth = $worth->add($orderTotalPrice);
                    $baseLeft = $baseLeft->subtract($orderTotalPrice);
                } else {
                    $amountCoefficient = $baseLeft->divide(
                        $this->moneyWrapper->format($orderTotalPrice)
                    );

                    // Taking just a portion of the order based on the base left
                    $quoteWorth = $ask->getAmount()->multiply(
                        $this->moneyWrapper->format($amountCoefficient)
                    );

                    $amountToReceive = $amountToReceive->add($quoteWorth);
                    $worth = $worth->add($baseLeft);
                    $baseLeft = $baseLeft->subtract($baseLeft);
                }
            }
        } while ($shouldContinue);

        if (!$market->isTokenMarket() && !$amountToReceive->isZero()) {
            // When buying in coin markets, the fee is taken from the amount that the user receives
            $amountToReceive = $amountToReceive->subtract($amountToReceive->multiply($feeRate));
        }

        return new CheckTradeResult($amountToReceive, $worth);
    }

    public function getExpectedDonationReversedResult(
        Market $market,
        string $amountToReceive,
        string $feeRate
    ): CheckTradeResult {
        /** @var Crypto $base */
        $base = $market->getBase();
        /** @var Token $quote */
        $quote = $market->getQuote();

        $useCryptoMarket = Symbols::WEB !== $base->getSymbol() && !$quote->containsExchangeCrypto($base);

        if ($useCryptoMarket) {
            $mintmeCurrency = $this->cryptoManager->findBySymbol(Symbols::WEB);
            $market->setBase($mintmeCurrency);
            $resultMintme = $this->getExpectedDonationReversedResult($market, $amountToReceive, $feeRate);
            $market->setBase($base);
            $market->setQuote($mintmeCurrency);
            $mintmeAmount = $this->moneyWrapper->format($resultMintme->getExpectedAmount());
            $resultCoin = $this->getExpectedBuyReversedResult($market, $mintmeAmount, "0");

            return new CheckTradeResult($resultCoin->getExpectedAmount(), $resultMintme->getWorth());
        }

        $baseSymbol = $base->getMoneySymbol();
        $quoteSymbol = $quote->getMoneySymbol();

        $amountLeft = $this->moneyWrapper->parse($amountToReceive, $quoteSymbol);
        $worth = $this->moneyWrapper->parse('0', $baseSymbol);

        $offset = 0;
        $limit = 100;

        do {
            $pendingSellOrders = $this->getPendingSellOrders($market, $offset, $limit);
            $shouldContinue = count($pendingSellOrders) >= $limit;
            $offset += $limit;

            foreach ($pendingSellOrders as $ask) {
                if ($amountLeft->isZero()) {
                    $shouldContinue = false;

                    break;
                }

                $amount = $amountLeft->lessThanOrEqual($ask->getAmount())
                    ? $amountLeft
                    : $ask->getAmount();
                $price = $ask->getPrice();
                $deal = $price->multiply($this->moneyWrapper->format($amount));
                $fee = $deal->multiply($feeRate);
                $worth = $worth->add($deal)->add($fee);

                $amountLeft = $amountLeft->subtract($amount);
            }
        } while ($shouldContinue);

        return new CheckTradeResult($worth, $amountLeft);
    }

    public function getExpectedBuyReversedResult(
        Market $market,
        string $amountToReceive,
        string $feeRate
    ): CheckTradeResult {
        $quote = $market->getQuote();

        $baseSymbol = $market->getBase()->getSymbol();

        $quoteSymbol = $quote instanceof Token
            ? Symbols::TOK
            : $quote->getSymbol();

        $amountLeft = $this->moneyWrapper->parse($amountToReceive, $quoteSymbol);

        $one = $this->moneyWrapper->parse('1', $quoteSymbol);
        $fee = $this->moneyWrapper->parse($feeRate, $quoteSymbol);
        $oneMinusFee = $one->subtract($fee);
        $denominator = $this->moneyWrapper->format($oneMinusFee);

        if (!$market->isTokenMarket() && !$amountLeft->isZero()) {
            $amountLeft = $amountLeft->divide($denominator);
        }

        $worth = $this->moneyWrapper->parse('0', $baseSymbol);

        $offset = 0;
        $limit = 100;

        do {
            $pendingSellOrders = $this->getPendingSellOrders($market, $offset, $limit);
            $shouldContinue = count($pendingSellOrders) >= $limit;
            $offset += $limit;

            foreach ($pendingSellOrders as $ask) {
                if ($amountLeft->isZero()) {
                    $shouldContinue = false;

                    break;
                }

                $amount = $amountLeft->lessThanOrEqual($ask->getAmount())
                    ? $amountLeft
                    : $ask->getAmount();

                $price = $ask->getPrice();
                $deal = $price->multiply($this->moneyWrapper->format($amount));
                $worth = $worth->add($deal);

                $amountLeft = $amountLeft->subtract($amount);
            }
        } while ($shouldContinue);

        if ($market->isTokenMarket() && !$worth->isZero()) {
            $worth = $worth->divide($denominator);
        }

        return new CheckTradeResult($worth, $amountLeft);
    }

    /** {@inheritdoc} */
    public function getKLineStatDaily(Market $market): array
    {
        $stats = $this->marketFetcher->getKLineStat(
            $this->marketNameConverter->convert($market),
            (new \DateTimeImmutable('1970-01-01 12:00:00'))->getTimestamp(),
            (new \DateTimeImmutable())->getTimestamp(),
            60 * 60 * 24
        );

        return array_map(function (array $line) use ($market) {
            return new LineStat(
                (new \DateTimeImmutable())->setTimestamp($line[0]),
                $this->moneyWrapper->parse($line[1], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[2], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[3], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[4], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[5], $this->getSymbol($market->getQuote())),
                $this->moneyWrapper->parse($line[6], $this->getSymbol($market->getQuote())),
                $market
            );
        }, $stats);
    }

    /** {@inheritdoc} */
    public function getKLineStatByPeriod(Market $market, string $period): array
    {
        if (self::PERIOD_TYPE_WEEK === $period) {
            $stats = $this->marketFetcher->getKLineStat(
                $this->marketNameConverter->convert($market),
                (new \DateTimeImmutable())->sub(new DateInterval('P7D'))->getTimestamp(),
                (new \DateTimeImmutable())->getTimestamp(),
                60 * 60 * 24
            );
        } elseif (self::PERIOD_TYPE_MONTH === $period) {
            $stats = $this->marketFetcher->getKLineStat(
                $this->marketNameConverter->convert($market),
                (new \DateTimeImmutable())->sub(new DateInterval('P30D'))->getTimestamp(),
                (new \DateTimeImmutable())->getTimestamp(),
                60 * 60 * 24
            );
        } else {
            $stats = $this->marketFetcher->getKLineStat(
                $this->marketNameConverter->convert($market),
                (new \DateTimeImmutable())->sub(new DateInterval('P6M'))->getTimestamp(),
                (new \DateTimeImmutable())->getTimestamp(),
                60 * 60 * 24
            );
        }

        return array_map(function (array $line) use ($market) {
            $lines = array_map(function ($l) use ($market) {
                return $this->moneyWrapper->parse($l, $this->getSymbol($market->getQuote()));
            }, array_slice($line, 1, 6));

            return new LineStat(
                (new \DateTimeImmutable())->setTimestamp($line[0]),
                $lines[0],
                $lines[1],
                $lines[2],
                $lines[3],
                $lines[4],
                $lines[5],
                $market,
            );
        }, $stats);
    }

    /** {@inheritdoc} */
    public function getMarketStatus(Market $market, int $period = self::DAY_PERIOD): array
    {
        return $this->marketFetcher->getMarketInfo(
            $this->marketNameConverter->convert($market),
            $period
        );
    }

    /**
     * @param array $result
     * @param Market $market
     * @param bool $reverseBaseQuote
     * @return Order[]
     */
    private function parsePendingOrders(array $result, Market $market, bool $reverseBaseQuote = false): array
    {
        $orders = array_key_exists('orders', $result)
            ? $result['orders']
            : $result;

        $filtered = [];

        if ($reverseBaseQuote) {
            $market = BaseQuote::reverseMarket($market);
        }

        array_walk($orders, function (array $orderData) use ($market, &$filtered): void {
            $user = $this->userManager->find($orderData['user']);

            if (!$user) {
                return;
            }

            $filtered[] = new Order(
                $orderData['id'],
                $user,
                null,
                $market,
                $this->moneyWrapper->parse(
                    (string)$orderData['left'],
                    $this->getSymbol($market->getQuote())
                ),
                $orderData['side'],
                $this->moneyWrapper->parse(
                    (string)$orderData['price'],
                    $this->getSymbol($market->getBase())
                ),
                Order::PENDING_STATUS,
                $this->moneyWrapper->parse(
                    (string)$orderData['maker_fee'],
                    $this->getSymbol($market->getQuote())
                ),
                !empty($orderData['mtime']) ? intval($orderData['mtime']) : null,
                !empty($orderData['ctime']) ? intval($orderData['ctime']) : null
            );
        });

        return $filtered;
    }

    /**
     * @param array $result
     * @param Market $market
     * @param bool $reverseBaseQuote
     * @return Order[]
     */
    private function parseExecutedOrders(array $result, Market $market, bool $reverseBaseQuote = false): array
    {
        /** @var Order[] */
        $orders = [];

        if ($reverseBaseQuote) {
            $market = BaseQuote::reverseMarket($market);
        }

        foreach ($result as $orderData) {
            $user = array_key_exists('maker_id', $orderData)
                ? $this->userManager->find($orderData['maker_id'])
                : null;

            if (!$user) {
                continue;
            }

            $timestamp = isset($orderData['time'])
                ? intval($orderData['time'])
                : null;

            if ($timestamp && $this->limitHistoryConfig->getFromDate()->getTimestamp() > $timestamp) {
                continue;
            }

            $orders[] = new Order(
                $orderData['id'],
                $user,
                !empty($orderData['taker_id'])
                    ? $this->userManager->find($orderData['taker_id'])
                    : null,
                $market,
                $this->moneyWrapper->parse(
                    $orderData['amount'],
                    $this->getSymbol($market->getQuote())
                ),
                Order::SIDE_MAP[$orderData['type']],
                $this->moneyWrapper->parse(
                    $orderData['price'],
                    $this->getSymbol($market->getBase())
                ),
                Order::FINISHED_STATUS,
                $this->moneyWrapper->parse(
                    !empty($orderData['fee']) ? (string)$orderData['fee'] : '0',
                    $this->getSymbol($market->getQuote())
                ),
                $timestamp
            );
        }

        return $orders;
    }

    /**
     * @inheritdoc
     */
    public function parseDeals(
        array $result,
        array $markets,
        bool $reverseBaseQuote = false,
        bool $limitedResult = true
    ): array {
        $parsedDeals = [];

        foreach ($result as $dealData) {
            $timestamp = (int)$dealData['time'];

            if ($limitedResult && $this->limitHistoryConfig->getFromDate()->getTimestamp() > $timestamp) {
                continue;
            }

            $market = null;

            foreach ($markets as $m) {
                if ($this->marketNameConverter->convert($m) === $dealData['market']) {
                    $market = $m;

                    break;
                }
            }

            if ($reverseBaseQuote) {
                $market = BaseQuote::reverseMarketInPlace($market);
            }

            $parsedDeals[] = $this->prepareDeal($dealData, $market, $timestamp);
        }

        return $parsedDeals;
    }

    /**
     * @inheritdoc
     */
    public function parseDealsSingleMarket(
        array $result,
        Market $market,
        bool $limitedResult = true
    ): array {
        $parsedDeals = [];

        foreach ($result as $dealData) {
            $timestamp = (int)$dealData['time'];

            if ($limitedResult && $this->limitHistoryConfig->getFromDate()->getTimestamp() > $timestamp) {
                continue;
            }

            $parsedDeals[] = $this->prepareDeal($dealData, $market, $timestamp);
        }

        return $parsedDeals;
    }

    /**
     * @inheritdoc
     */
    private function prepareDeal(array $dealData, Market $market, int $timestamp): Deal
    {
        return new Deal(
            $dealData['id'],
            $timestamp,
            $dealData['user'],
            $dealData['side'] ?? Order::ALL_SIDE,
            $dealData['role'],
            $this->moneyWrapper->parse(
                $dealData['amount'],
                $this->getSymbol($market->getQuote())
            ),
            $this->moneyWrapper->parse(
                $dealData['price'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $dealData['deal'],
                $this->getSymbol($market->getQuote())
            ),
            $this->moneyWrapper->parse(
                $dealData['fee'],
                $this->getSymbol($market->getBase())
            ),
            $dealData['deal_order_id'],
            $dealData['order_id'] ?? 0,
            $market
        );
    }

    /**
     * @param Donation[] $donations
     * @return Deal[]
     */
    private function donationsToDeals(array $donations, User $user): array
    {
        $donations = array_map(function (Donation $donation) use ($user) {
            if (!$donation->getToken()) {
                // ToDo: Show these donations on frontend instead of skip it
                return null;
            }

            $isUserDonator = (int)$donation->getDonor()->getId() === $user->getId();

            $amount = $isUserDonator || !$donation->getReceiverAmount()
                ? $donation->getAmount()->subtract($donation->getFeeAmount())
                : $donation->getReceiverAmount()->subtract($donation->getReceiverFeeAmount());

            $fee = $isUserDonator
                ? $donation->getFeeAmount()
                : $donation->getReceiverFeeAmount() ?? $donation->getFeeAmount();

            $currency = $this->cryptoManager->findBySymbol(
                $isUserDonator
                    ? $donation->getCurrency()
                    : $donation->getReceiverCurrency()
            );

            if (!$currency) {
                return null;
            }

            $market = $this->marketFactory->create(
                $currency,
                $donation->getToken()
            );

            return new Deal(
                0,
                // +1s so it's ordered after crypto market deal
                $donation->getCreatedAt()->getTimestamp() + 1,
                (int)$donation->getDonor()->getId(),
                $isUserDonator ? self::BUY : self::SELL,
                $isUserDonator ? 2 : 1,
                $donation->getTokenAmount(),
                $amount,
                $this->moneyWrapper->parse('0', $donation->getCurrency()),
                $fee,
                0,
                0,
                $market
            );
        }, $donations);

        return array_filter($donations, fn ($donation) => !is_null($donation));
    }

    /** {@inheritdoc} */
    public function getMarketInfo(Market $market, int $period = self::DAY_PERIOD): MarketInfo
    {
        $result = $this->marketFetcher->getMarketInfo(
            $this->marketNameConverter->convert($market),
            $period
        );

        if (!$result) {
            throw new InvalidArgumentException();
        }

        $monthResult = $this->marketFetcher->getMarketInfo(
            $this->marketNameConverter->convert($market),
            self::MONTH_PERIOD
        );

        $buyDepth = $this->getBuyDepth($market);
        $quote = $market->getQuote();
        $soldOnMarket = $this->moneyWrapper->parse('0', $this->getSymbol($market->getBase()));

        if ($quote instanceof Token && $quote->isCreatedOnMintmeSite()) {
            $soldOnMarket = $this->soldOnMarket($quote);
        }

        $expires = new \DateTimeImmutable();

        if (isset($result['expires'], $monthResult['expires'])) {
            $expires = $expires->setTimestamp(min($result['expires'], $monthResult['expires']));
        } elseif (isset($result['expires'])) {
            $expires = $expires->setTimestamp($result['expires']);
        } elseif (isset($monthResult['expires'])) {
            $expires = $expires->setTimestamp($monthResult['expires']);
        } else {
            $expires = null;
        }

        $volumeDonation =  $this->moneyWrapper->parse(
            $result['volumeDonation'],
            $this->getSymbol($market->getQuote())
        );

        $dealDonationDay =  $this->moneyWrapper->parse(
            $result['dealDonation'],
            $this->getSymbol($market->getBase())
        );

        $dealDonationMonth =  $this->moneyWrapper->parse(
            $monthResult['dealDonation'],
            $this->getSymbol($market->getBase())
        );

        return new MarketInfo(
            $market->getBase()->getSymbol(),
            $market->getQuote()->getSymbol(),
            $this->moneyWrapper->parse(
                $result['last'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['volume'],
                $this->getSymbol($market->getQuote())
            )->add($volumeDonation),
            $this->moneyWrapper->parse(
                $result['open'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['close'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['high'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['low'],
                $this->getSymbol($market->getBase())
            ),
            $this->moneyWrapper->parse(
                $result['deal'],
                $this->getSymbol($market->getBase())
            )->add($dealDonationDay),
            $this->moneyWrapper->parse(
                $monthResult['deal'],
                $this->getSymbol($market->getBase())
            )->add($dealDonationMonth),
            $this->moneyWrapper->parse(
                $buyDepth,
                $this->getSymbol($market->getBase())
            ),
            $soldOnMarket,
            $volumeDonation,
            $expires
        );
    }

    public function getSummary(array $market): array
    {
        $result = $this->marketFetcher->getSummary(
            array_map(function (Market $market) {
                return $this->marketNameConverter->convert($market);
            }, $market)
        );

        return (new MarketSummaryFactory(
            $result,
            $market,
            $this->moneyWrapper,
            $this->marketNameConverter
        ))->create();
    }

    public function getOneSummary(Market $market): Summary
    {
        return $this->getSummary([$market])[0];
    }

    private function getSymbol(TradableInterface $tradable): string
    {
        return $tradable instanceof Token
            ? Symbols::TOK
            : $tradable->getSymbol();
    }

    /** {@inheritdoc} */
    public function getBuyDepth(Market $market): string
    {
        $orders = $this->getAllPendingBuyOrders($market);

        $zeroDepth = $this->moneyWrapper->parse(
            '0',
            $this->getSymbol($market->getBase())
        );

        /** @var Money $depthAmount */
        $depthAmount = array_reduce($orders, function (Money $sum, Order $order) {
            return $order->getPrice()->multiply(
                $this->moneyWrapper->format($order->getAmount())
            )->add($sum);
        }, $zeroDepth);

        return $this->moneyWrapper->format($depthAmount);
    }

    /** {@inheritdoc} */
    public function getSellOrdersSummary(Market $market, ?User $user = null): SellOrdersSummaryResult
    {
        $offset = 0;
        $limit = 100;
        $paginatedOrders = [];

        do {
            $pendingOrders = [];

            if ($user) {
                $pendingOrders = $this->marketFetcher->getPendingOrdersByUser(
                    $user->getId(),
                    $this->marketNameConverter->convert($market),
                    $offset,
                    $limit
                );

                $pendingOrders = array_filter($pendingOrders, function ($order) {
                    return self::SELL === $order['side'];
                });
            } else {
                $pendingOrders = $this->marketFetcher->getPendingOrders(
                    $this->marketNameConverter->convert($market),
                    $offset,
                    $limit,
                    self::SELL
                );
            }

            $moreOrders = $this->parsePendingOrders(
                $pendingOrders,
                $market
            );

            $paginatedOrders[] = $moreOrders;
            $offset += $limit;
        } while (count($moreOrders) >= $limit);

        $orders = array_merge([], ...$paginatedOrders);
        $zeroDepthBase = $this->moneyWrapper->parse(
            '0',
            $this->getSymbol($market->getBase())
        );

        /** @var Money $sellOrdersSum */
        $sellOrdersSum = array_reduce($orders, function (Money $sum, Order $order) {
            return $order->getPrice()->multiply(
                $this->moneyWrapper->format($order->getAmount())
            )->add($sum);
        }, $zeroDepthBase);

        $sellOrdersSum = $this->moneyWrapper->format($sellOrdersSum);

        $zeroDepthQuote = $this->moneyWrapper->parse(
            '0',
            $this->getSymbol($market->getQuote())
        );

        /** @var Money $quoteAmountSummary */
        $quoteAmountSummary = array_reduce($orders, function (Money $sum, Order $order) {
            return $order->getAmount()->add($sum);
        }, $zeroDepthQuote);

        $quoteAmountSummary = $this->moneyWrapper->format($quoteAmountSummary);

        return new SellOrdersSummaryResult($sellOrdersSum, $quoteAmountSummary);
    }

    public function getSellOrdersSummaryByUser(User $user, Market $market): array
    {
        $offset = 0;

        return $this->marketFetcher->getPendingOrdersByUser(
            $user->getId(),
            $this->marketNameConverter->convert($market),
            $offset,
        );
    }

    /** {@inheritdoc} */
    public function getBuyOrdersSummary(Market $market): BuyOrdersSummaryResult
    {
        $offset = 0;
        $limit = 100;
        $paginatedBuyOrders = [];

        do {
            $pendingOrders = $this->marketFetcher->getPendingOrders(
                $this->marketNameConverter->convert($market),
                $offset,
                $limit,
                self::BUY
            );

            $moreOrders = $this->parsePendingOrders(
                $pendingOrders,
                $market
            );

            $paginatedBuyOrders[] = $moreOrders;
            $offset += $limit;
        } while (count($moreOrders) >= $limit);

        $orders = array_merge([], ...$paginatedBuyOrders);

        $zeroDepthBase = $this->moneyWrapper->parse(
            '0',
            $this->getSymbol($market->getBase())
        );

        /** @var Money $buyOrdersSum */
        $buyOrdersSum = array_reduce($orders, function (Money $sum, Order $order) {
            return $sum->add(
                $order->getPrice()->multiply($this->moneyWrapper->format($order->getAmount()))
            );
        }, $zeroDepthBase);

        $buyOrdersSum = $this->moneyWrapper->format($buyOrdersSum);

        $zeroDepthQuote = $this->moneyWrapper->parse(
            '0',
            $this->getSymbol($market->getQuote())
        );

        /** @var Money $quoteAmountSummary */
        $quoteAmountSummary = array_reduce($orders, function (Money $sum, Order $order) {
            return $order->getAmount()->add($sum);
        }, $zeroDepthQuote);

        $quoteAmountSummary = $this->moneyWrapper->format($quoteAmountSummary);

        return new BuyOrdersSummaryResult($buyOrdersSum, $quoteAmountSummary);
    }


    private function getPendingOrders(
        Market $market,
        int $offset,
        int $limit,
        int $side,
        array $pendingOrdersSliced = [],
        array $pricesKeys = []
    ): array {
        $pendingOrders = $this->marketFetcher->getPendingOrders(
            $this->marketNameConverter->convert($market),
            $offset,
            $limit,
            $side
        );
        $ordersGroupedCount = count($pricesKeys);

        foreach ($pendingOrders as $pendingOrder) {
            $orderPrice = $pendingOrder['price'];
            $pendingOrdersSliced[] = $pendingOrder;

            if (!isset($pricesKeys[$orderPrice])) {
                $pricesKeys[$orderPrice] = true;
                $ordersGroupedCount++;
            }

            if ($ordersGroupedCount === $limit) {
                break;
            }
        }

        if ($ordersGroupedCount === $limit || count($pendingOrders) < $limit) {
            return $pendingOrdersSliced;
        }

        return $this->getPendingOrders(
            $market,
            $offset +  $limit,
            $limit,
            $side,
            $pendingOrdersSliced,
            $pricesKeys
        );
    }

    public function getTokenSellOrdersSummary(Token $token, User $user): string
    {
        $markets = $this->marketFactory->createTokenMarkets($token);
        $totalSellOrders = 0;

        foreach ($markets as $market) {
            $totalSellOrders += (float) $this->getSellOrdersSummary($market, $user)->getQuoteAmount();
        }

        return (string)$totalSellOrders;
    }


    public function soldOnMarket(Token $token): Money
    {
        if (!$token->isCreatedOnMintmeSite()) {
            return $this->moneyWrapper->parse('0', Symbols::TOK);
        }

        $init = $this->moneyWrapper->parse(
            (string)$this->parameterBag->get('token_quantity'),
            Symbols::TOK
        );

        $balanceView = $this->balanceHandler->balance($token->getOwner(), $token);

        $withdrawn = $token->getWithdrawn();

        $pendingTokenWithdrawals = $token->isDeployed()
            ? $token->getOwner()->getPendingTokenWithdrawals()
            : null;

        /** @var Transaction[]|null $pendingWithdrawalsOnBlockchain */
        $pendingWithdrawalsOnBlockchain = $token->isDeployed()
            ? $this->contractHandler->getPendingWithdrawals($token->getOwner(), AssetType::TOKEN)
            : null;

        if ($pendingTokenWithdrawals instanceof PersistentCollection) {
            foreach ($pendingTokenWithdrawals->getValues() as $pending) {
                if ($pending->getToken() == $token) {
                    $withdrawn = $withdrawn->add($pending->getAmount()->getAmount());
                }
            }
        }

        if ($pendingWithdrawalsOnBlockchain) {
            foreach ($pendingWithdrawalsOnBlockchain as $pending) {
                if ($pending->getTradable() instanceof Token && $pending->getTradable() === $token) {
                    $withdrawn = $withdrawn->add($pending->getAmount());
                }
            }
        }

        return $init
            ->subtract($balanceView->getAvailable())
            ->subtract($balanceView->getFreeze())
            ->subtract($withdrawn);
    }
}
