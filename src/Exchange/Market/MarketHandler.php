<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Deal;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Factory\MarketSummaryFactory;
use App\Exchange\Market;
use App\Exchange\Market\Model\LineStat;
use App\Exchange\Market\Model\SellOrdersSummaryResult;
use App\Exchange\Market\Model\Summary;
use App\Exchange\MarketInfo;
use App\Exchange\Order;
use App\Manager\CryptoManagerInterface;
use App\Manager\DonationManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
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

    private MarketFetcherInterface $marketFetcher;
    private MoneyWrapperInterface $moneyWrapper;
    private UserManagerInterface $userManager;
    private MarketNameConverterInterface $marketNameConverter;
    private DonationManagerInterface $donationManager;
    private MarketFactoryInterface $marketFactory;
    private CryptoManagerInterface $cryptoManager;
    private BalanceHandlerInterface $balanceHandler;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        MarketFetcherInterface $marketFetcher,
        MoneyWrapperInterface $moneyWrapper,
        UserManagerInterface $userManager,
        MarketNameConverterInterface $marketNameConverter,
        DonationManagerInterface $donationManager,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        BalanceHandlerInterface $balanceHandler,
        ParameterBagInterface $parameterBag
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
    }

    /** {@inheritdoc} */
    public function getExecutedOrder(Market $market, int $id, int $limit = 100): Order
    {
        $orders = $this->getExecutedOrders($market, 0, $limit);

        foreach ($orders as $order) {
            if ($order->getId() === $id) {
                return $order;
            }
        }

        throw new Exception("Order not found");
    }

    public function getPendingOrder(Market $market, int $id): Order
    {
        $order = $this->marketFetcher->getPendingOrder(
            $this->marketNameConverter->convert($market),
            $id
        );

        return $this->parsePendingOrders([$order], $market)[0];
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
        int $donationsOffset = 0
    ): array {
        $marketDeals = array_map(function (Market $market) use ($user, $offset, $limit, $reverseBaseQuote, $donationsOffset) {
            return $this->parseDeals(
                $this->marketFetcher->getUserExecutedHistory(
                    $user->getId(),
                    $this->marketNameConverter->convert($market),
                    $offset - $donationsOffset,
                    $limit
                ),
                $market,
                $reverseBaseQuote
            );
        }, $markets);

        $donations = $this->donationsToDeals($this->donationManager->getAllUserRelated($user), $user);
        $donations = array_slice($donations, $donationsOffset, count($donations) - $donationsOffset);
        $deals = array_merge($marketDeals ? array_merge(...$marketDeals) : [], $donations);

        uasort($deals, static function (Deal $lDeal, Deal $rDeal) {
            return $lDeal->getTimestamp() < $rDeal->getTimestamp();
        });

        return array_slice($deals, 0, $limit);
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
            return $this->parsePendingOrders(
                $this->marketFetcher->getPendingOrdersByUser(
                    $user->getId(),
                    $this->marketNameConverter->convert($market),
                    $offset,
                    $limit
                ),
                $market,
                $reverseBaseQuote
            );
        }, $markets);

        $orders = $marketOrders ? array_merge(...$marketOrders) : [];

        uasort($orders, static function (Order $lOrder, Order $rOrder) {
            return $lOrder->getTimestamp() > $rOrder->getTimestamp();
        });

        $orders=array_slice($orders, 0, $limit);

        return $orders;
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
                    $this->getSymbol($market->getQuote())
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
                isset($orderData['time']) ? intval($orderData['time']) : null
            );
        }

        return $orders;
    }

    /**
     * @param array $result
     * @param Market $market
     * @param bool $reverseBaseQuote
     * @return Deal[]
     */
    private function parseDeals(array $result, Market $market, bool $reverseBaseQuote = false): array
    {
        if ($reverseBaseQuote) {
            $market = BaseQuote::reverseMarket($market);
        }

        $deals = array_map(function (array $dealData) use ($market) {
            return new Deal(
                $dealData['id'],
                (int)$dealData['time'],
                $dealData['user'],
                $dealData['side'],
                $dealData['role'],
                $this->moneyWrapper->parse(
                    $dealData['amount'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $dealData['price'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $dealData['deal'],
                    $this->getSymbol($market->getQuote())
                ),
                $this->moneyWrapper->parse(
                    $dealData['fee'],
                    $this->getSymbol($market->getQuote())
                ),
                $dealData['deal_order_id'],
                $dealData['order_id'] ?? 0,
                $market
            );
        }, $result);

        // Filter deals and return not donation deals
        return array_filter($deals, fn(Deal $deal) => 0 !== $deal->getOrderId() && 0 !== $deal->getDealOrderId());
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

            return new Deal(
                0,
                $donation->getCreatedAt()->getTimestamp(),
                (int)$donation->getDonor()->getId(),
                (int)$donation->getDonor()->getId() === $user->getId() ? self::BUY : self::SELL,
                (int)$donation->getDonor()->getId() === $user->getId() ? 2 : 1,
                $donation->getAmount()->subtract($donation->getFeeAmount()),
                $this->moneyWrapper->parse('0', $donation->getCurrency()),
                $this->moneyWrapper->parse('0', $donation->getCurrency()),
                $donation->getFeeAmount(),
                0,
                0,
                $this->marketFactory->create(
                    $this->cryptoManager->findBySymbol($donation->getCurrency()),
                    $donation->getToken()
                )
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

        if ($quote instanceof Token && $quote->isMintmeToken()) {
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

    private function getSymbol(TradebleInterface $tradable): string
    {
        return $tradable instanceof Token
            ? Symbols::TOK
            : $tradable->getSymbol();
    }

    /** {@inheritdoc} */
    public function getBuyDepth(Market $market): string
    {
        $offset = 0;
        $limit = 100;
        $paginatedOrders = [];

        do {
            $moreOrders = $this->getPendingBuyOrders($market, $offset, $limit);
            $paginatedOrders[] = $moreOrders;
            $offset += $limit;
        } while (count($moreOrders) >= $limit);

        $orders = array_merge([], ...$paginatedOrders);

        $zeroDepth = $this->moneyWrapper->parse(
            '0',
            $market->isTokenMarket() ? Symbols::TOK : $market->getQuote()->getSymbol()
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
    public function getSellOrdersSummary(Market $market): SellOrdersSummaryResult
    {
        $offset = 0;
        $limit = 100;
        $paginatedOrders = [];

        do {
            $moreOrders = $this->getPendingSellOrders($market, $offset, $limit);
            $paginatedOrders[] = $moreOrders;
            $offset += $limit;
        } while (count($moreOrders) >= $limit);

        $orders = array_merge([], ...$paginatedOrders);

        $zeroDepth = $this->moneyWrapper->parse('0', Symbols::TOK);

        /** @var Money $sellOrdersSum */
        $sellOrdersSum = array_reduce($orders, function (Money $sum, Order $order) {
            return $order->getPrice()->multiply(
                $this->moneyWrapper->format($order->getAmount())
            )->add($sum);
        }, $zeroDepth);

        $sellOrdersSum = $this->moneyWrapper->format($sellOrdersSum);

        /** @var Money $quoteAmountSummary */
        $quoteAmountSummary = array_reduce($orders, function (Money $sum, Order $order) {
            return $order->getAmount()->add($sum);
        }, $zeroDepth);

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

    public function soldOnMarket(Token $token): Money
    {
        $mintmeCrypto = $this->cryptoManager->findBySymbol(Symbols::WEB);
        $market = new Market($token->getCrypto() ?? $mintmeCrypto, $token);
        $available = $this->balanceHandler->balance($token->getProfile()->getUser(), $token)->getAvailable();
        $init = $this->moneyWrapper->parse(
            (string)$this->parameterBag->get('token_quantity'),
            Symbols::TOK
        );

        $offset = 0;
        $limit = 100;

        do {
            $ownPendingOrders = $this->getPendingOrdersByUser(
                $token->getOwner(),
                [$market],
                $offset,
                $limit
            );

            foreach ($ownPendingOrders as $order) {
                if (Order::SELL_SIDE === $order->getSide()) {
                    $available = $available->add($order->getAmount());
                }
            }

            $ordersCount = count($ownPendingOrders);
            $offset += $limit;
        } while ($ordersCount >= $limit);

        return $init->subtract($available);
    }
}
