<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Market;

use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\Donation;
use App\Entity\PendingTokenWithdraw;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Deal;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcherInterface;
use App\Exchange\Market\MarketHandler;
use App\Exchange\Market\Model\BuyOrdersSummaryResult;
use App\Exchange\Market\Model\LineStat;
use App\Exchange\Market\Model\SellOrdersSummaryResult;
use App\Exchange\Order;
use App\Manager\CryptoManagerInterface;
use App\Manager\DonationManagerInterface;
use App\Manager\UserManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Tests\Mocks\MockMoneyWrapperWithDecimal;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;

class MarketHandlerTest extends TestCase
{

    use MockMoneyWrapper;
    use MockMoneyWrapperWithDecimal;

    public function testGetExpectedSellResult(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->willReturn(
                $this->getPendingOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapperWithDecimal(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket("BASE", "TOK");
        $order = $mh->getExpectedSellResult($market, "10", "1");

        $this->assertEquals(
            "0",
            $order->getExpectedAmount()->getAmount()
        );
    }

    /** @dataProvider getExpectedSellResultProvider */
    public function testGetExpectedSellNonZeroResult(
        array $orders,
        string $amount,
        string $feeRate,
        string $result
    ): void {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->willReturn(
                $orders
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapperWithDecimal(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket("BASE", "TOK");
        $order = $mh->getExpectedSellResult($market, $amount, $feeRate);

        $this->assertEquals(
            $result,
            $this->mockMoneyWrapperWithDecimal()->format($order->getExpectedAmount())
        );
    }

    /** @dataProvider getExpectedSellReversedResultProvider */
    public function testGetExpectedSellReversedNonZeroResult(
        array $orders,
        string $amountToReceive,
        string $feeRate,
        string $result
    ): void {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->willReturn(
                $orders
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapperWithDecimal(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket("BASE", "TOK");
        $order = $mh->getExpectedSellReversedResult($market, $amountToReceive, $feeRate);

        $this->assertEquals(
            $result,
            $this->mockMoneyWrapperWithDecimal()->format($order->getExpectedAmount())
        );
    }

    public function getExpectedSellReversedResultProvider(): array
    {
        return [
            [
                'orders' => [
                    ['user'=>1,'id'=>2,'left'=>'3','side'=>0,'price'=>'6','mtime'=>7,'maker_fee'=>8.,'taker_fee'=>8.],
                    ['user'=>1,'id'=>2,'left'=>'3','side'=>1,'price'=>'6','mtime'=>7,'maker_fee'=>8.,'taker_fee'=>8.],
                    ['user'=>1,'id'=>2,'left'=>'3','side'=>2,'price'=>'6','mtime'=>7,'maker_fee'=>6.,'taker_fee'=>6.],
                ],
                'amountToReceive' => '53',
                'feeRate' => '0.02',
                'result' => '9.000000000000',
            ],
            [
                'orders' => [
                    ['user'=>1,'id'=>2,'left'=>'998000000000','side'=>2,'price'=>'9223372036854775807','mtime'=>1659079702,'maker_fee'=>'2000000000','taker_fee'=>'2000000000'],
                ],
                'amountToReceive' => '9204925292781.066256384000000000',
                'feeRate' => '0.002',
                'result' => '0.000001000000',
            ],
            [
                'orders' => [
                    ['user'=>1,'id'=>2,'left'=>'0.998','side'=>2,'price'=>'10','mtime'=>1659079702,'maker_fee'=>'0.002','taker_fee'=>'0.002'],
                ],
                'amountToReceive' => '9.946068',
                'feeRate' => '0.002',
                'result' => '0.996600000000',
            ],
        ];
    }

    public function getExpectedSellResultProvider(): array
    {
        return [
            [
                'orders' => [
                    ['user'=>1,'id'=>2,'left'=>'3','side'=>0,'price'=>'6','mtime'=>7,'maker_fee'=>8.,'taker_fee'=>8.],
                    ['user'=>1,'id'=>2,'left'=>'3','side'=>1,'price'=>'6','mtime'=>7,'maker_fee'=>8.,'taker_fee'=>8.],
                    ['user'=>1,'id'=>2,'left'=>'3','side'=>2,'price'=>'6','mtime'=>7,'maker_fee'=>6.,'taker_fee'=>6.],
                ],
                'amount' => '9',
                'feeRate' => '0.02',
                'result' => '52.920000000000000000',
            ],
            [
                'orders' => [
                    ['user'=>1,'id'=>2,'left'=>'998000000000','side'=>2,'price'=>'9223372036854775807','mtime'=>1659079702,'maker_fee'=>'2000000000','taker_fee'=>'2000000000'],
                ],
                'amount' => '1',
                'feeRate' => '0.002',
                'result' => '9204925292781066256.384000000000000000',
            ],
            [
                'orders' => [
                    ['user'=>1,'id'=>2,'left'=>'0.998','side'=>2,'price'=>'10','mtime'=>1659079702,'maker_fee'=>'0.002','taker_fee'=>'0.002'],
                ],
                'amount' => '0.9966',
                'feeRate' => '0.002',
                'result' => '9.946068000000000000',
            ],
        ];
    }

    public function testGetExpectedBuyResult(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->willReturn(
                $this->getPendingOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapperWithDecimal(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket("BASE", "TOK");
        $order = $mh->getExpectedBuyResult($market, "10", "1");

        $this->assertEquals(
            [
                "0.000000000000",
                "10.000000000000000000",
            ],
            [
                $this->mockMoneyWrapperWithDecimal()->format($order->getExpectedAmount()),
                $this->mockMoneyWrapperWithDecimal()->format($order->getWorth()),
            ]
        );
    }

    public function testGetExpectedBuyNonZeroResult(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->willReturn(
                $this->getPendingOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapperWithDecimal(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket("BASE", "TOK");
        $order = $mh->getExpectedBuyResult($market, "54", "0.02");

        $this->assertEquals(
            [
                "8.820000000000",
                "54.000000000000000000",
            ],
            [
                $this->mockMoneyWrapperWithDecimal()->format($order->getExpectedAmount()),
                $this->mockMoneyWrapperWithDecimal()->format($order->getWorth()),
            ]
        );
    }

    public function testGetExpectedBuyReversedNonZeroResult(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->willReturn(
                $this->getPendingOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapperWithDecimal(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket("BASE", "TOK");
        $order = $mh->getExpectedBuyReversedResult($market, "8.82", "0.02");

        $this->assertEquals(
            [
                "54.000000000000000000",
                "0.000000000000",
            ],
            [
                $this->mockMoneyWrapperWithDecimal()->format($order->getExpectedAmount()),
                $this->mockMoneyWrapperWithDecimal()->format($order->getWorth()),
            ]
        );
    }

    public function testGetSellOrdersSummary(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrdersByUser')
            ->willReturn(
                $this->getPendingOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket("BASE", "QUOTE");
        $summary = $mh->getSellOrdersSummary($market, $this->mockUser(2));
        $this->assertEquals(
            ['18', '3'],
            [$summary->getBaseAmount(), $summary->getQuoteAmount()]
        );
    }

    public function testGetBuyOrdersSummary(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->willReturn(
                $this->getPendingOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket("BASE", "QUOTE");
        $summary = $mh->getBuyOrdersSummary($market);
        $this->assertEquals(
            ['54', '9'],
            [$summary->getBasePrice(), $summary->getQuoteAmount()]
        );
    }

    public function testGetExecutedOrder(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getExecutedOrders')
            ->with('convertedmarket', 0, 100)
            ->willReturn(
                $this->getExecutedOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $order = $mh->getExecutedOrder(
            $this->mockMarket('FOO', 'FOO'),
            2
        );

        $this->assertEquals(2, $order->getId());
    }

    public function testGetExecutedOrderWithNonExistentOrder(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getExecutedOrders')
            ->with('convertedmarket', 0, 100)
            ->willReturn(
                $this->getExecutedOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $this->assertNull($mh->getExecutedOrder(
            $this->mockMarket('FOO', 'FOO'),
            123
        ));
    }

    public function testGetExecutedOrders(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getExecutedOrders')
            ->with('convertedmarket', 2, 100)
            ->willReturn(
                $this->getExecutedOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturnCallback(function (int $id) {
            return $this->mockUser($id);
        });

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $orders = $mh->getExecutedOrders(
            $this->mockMarket('FOO', 'FOO', true),
            2
        );

        $this->assertEquals(array_map(function (array $row) {
            $row['fee'] = $row['fee'] ?? 0;
            $row['type'] = 'all' === $row['type']
                ? 0
                :
                ('sell' === $row['type'] ? 1 : ('buy' === $row['type'] ? 2 : -1));

            return $row;
        }, $this->getExecutedOrders()), array_map(function (Order $order) {
            return [
                'maker_id' => $order->getMaker()->getId(),
                'id' => $order->getId(),
                'taker_id' => $order->getTaker() ? $order->getTaker()->getId() : null,
                'amount' => $order->getAmount()->getAmount(),
                'type' => $order->getSide(),
                'price' => $order->getPrice()->getAmount(),
                'time' => $order->getTimestamp(),
                'fee' => $order->getFee()->getAmount(),
            ];
        }, $orders));
    }

    public function testGetPendingOrder(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrder')
            ->with('convertedmarket', 4)
            ->willReturn(
                $this->getPendingOrders()[0]
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturnCallback(function (int $id) {
            return $this->mockUser($id);
        });

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $order = $mh->getPendingOrder(
            $this->mockMarket('FOO', 'BAR'),
            4
        );

        $this->assertEquals($this->getPendingOrders()[0], [
            'user' => $order->getMaker()->getId(),
            'id'=> $order->getId(),
            'left'=> $order->getAmount()->getAmount(),
            'side'=> $order->getSide(),
            'price'=> $order->getPrice()->getAmount(),
            'mtime'=> $order->getTimestamp(),
            'maker_fee'=> $order->getFee()->getAmount(),
            'taker_fee'=> $order->getFee()->getAmount(),
        ]);
    }

    public function testGetPendingOrders(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->with('convertedmarket', 4, 100)
            ->willReturn($this->getPendingOrders());

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturnCallback(function (int $id) {
            return $this->mockUser($id);
        });

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $buyOrders = $mh->getPendingBuyOrders(
            $this->mockMarket('FOO', 'BAR'),
            4
        );

        $sellOrders = $mh->getPendingSellOrders(
            $this->mockMarket('FOO', 'BAR'),
            4
        );

        $mockBuySummary = $this->mockBuySummary('100', '200');
        $mockSellSummary = $this->mockSellSummary('100', '200');
        $buyPendingSummary = $this->convertBuyPendingSummary('100', '200');
        $sellPendingSummary = $this->convertSellPendingSummary('100', '200');

        $this->assertEquals($this->getPendingOrders(), $this->convertPending($buyOrders));
        $this->assertEquals($this->getPendingOrders(), $this->convertPending($sellOrders));
        $this->assertEquals($mockBuySummary->getBasePrice(), $buyPendingSummary->getBasePrice());
        $this->assertEquals($mockSellSummary->getQuoteAmount(), $sellPendingSummary->getQuoteAmount());
    }

    public function testGetPendingOrdersByUser(): void
    {
        $ordersWithOffset = [$this->getPendingOrders()[1], $this->getPendingOrders()[2]];

        $fetcher = $this->mockMarketFetcher();
        $fetcher->expects($this->at(0))
            ->method('getPendingOrdersByUser')
            ->with(1, 'convertedmarket', 0)
            ->willReturn($ordersWithOffset);

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturnCallback(function (int $id) {
            return $this->mockUser($id);
        });

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $orders = $mh->getPendingOrdersByUser(
            $this->mockUser(1),
            [$this->mockMarket('FOO', 'BAR')],
            0,
        );

        $rawOrders = array_map(function (Order $order) {
            return [
                'user' => $order->getMaker()->getId(),
                'id'=> $order->getId(),
                'left'=> $order->getAmount()->getAmount(),
                'side'=> $order->getSide(),
                'price'=> $order->getPrice()->getAmount(),
                'mtime'=> $order->getTimestamp(),
                'maker_fee'=> (float)$order->getFee()->getAmount(),
                'taker_fee'=> (float)$order->getFee()->getAmount(),
            ];
        }, $orders);

        $rawOrdersWithOffset = array_slice($rawOrders, 0, 100);

        // returns pending orders with offset 1
        $this->assertEquals(
            $ordersWithOffset,
            $rawOrdersWithOffset
        );
    }

    public function testGetUserExecutedHistory(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getUserExecutedHistory')
            ->with(1, 'convertedmarket,convertedmarket', 0, 100)
            ->willReturn(
                $this->getDeals()
            );

        $marketFactory = $this->mockMarketFactory();
        $marketFactory->method('create')->willReturn($this->mockMarket());

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $marketFactory,
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $deals = $mh->getUserExecutedHistory(
            $this->mockUser(1),
            [
                $this->mockMarket('FOO', 'BAR'),
                $this->mockMarket('BAZ', 'QUX', true),
            ]
        );
        $marketDeals = [$deals[0], $deals[1]];
        $this->assertEquals([$this->getDeals()[0], $this->getDeals()[1]], array_map(function (Deal $deal) {
            return [
                'id' => $deal->getId(),
                'time' => $deal->getTimestamp(),
                'user' => $deal->getUserId(),
                'side' => $deal->getSide(),
                'role' => $deal->getRole(),
                'amount' => $deal->getAmount()->getAmount(),
                'price' => $deal->getPrice()->getAmount(),
                'deal' => $deal->getDeal()->getAmount(),
                'fee' => $deal->getFee()->getAmount(),
                'deal_order_id' => $deal->getDealOrderId(),
                'order_id' => $deal->getOrderId(),
                'market' => 'convertedmarket',
            ];
        }, $marketDeals));

        $donation = $deals[2];
        $this->assertEquals(
            [
                "id" => 0,
                "time" => 1,
                "user" => 1,
                "side" => 2,
                "role" => 2,
                "amount" => "1",
                "price" => "0",
                "deal" => "0",
                "fee" => "1",
                "deal_order_id" => 0,
                "order_id" => 0,
            ],
            [
                'id' => $donation->getId(),
                'time' => $donation->getTimestamp(),
                'user' => $donation->getUserId(),
                'side' => $donation->getSide(),
                'role' => $donation->getRole(),
                'amount' => $donation->getAmount()->getAmount(),
                'price' => $donation->getPrice()->getAmount(),
                'deal' => $donation->getDeal()->getAmount(),
                'fee' => $donation->getFee()->getAmount(),
                'deal_order_id' => $donation->getDealOrderId(),
                'order_id' => $donation->getOrderId(),
            ],
        );
    }

    public function testGetKLineStatDaily(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getKLineStat')
            ->with('convertedmarket', 43200, $this->anything(), 86400)
            ->willReturn(
                $this->getKlineStats()
            );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $this->mockUserManager(),
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket('FOO', 'BAR');
        $stats = $mh->getKLineStatDaily($market);

        $this->assertEquals($this->getKlineStats(), array_map(function (LineStat $stat) use ($market) {
            $this->assertEquals($stat->getMarket(), $market);

            return [
                $stat->getTime(),
                $stat->getOpen()->getAmount(),
                $stat->getClose()->getAmount(),
                $stat->getHighest()->getAmount(),
                $stat->getLowest()->getAmount(),
                $stat->getVolume()->getAmount(),
                $stat->getAmount()->getAmount(),
            ];
        }, $stats));
    }

    public function testGetMarketInfo(): void
    {
        $data = [
            'last' => '1',
            'volume' => '2',
            'open' => '3',
            'close' => '4',
            'high' => '5',
            'low' => '6',
            'deal' => '7',
            'volumeDonation' => '8',
            'dealDonation' => '9',
        ];

        $dataMonth = [
            'deal' => '2',
            'dealDonation' => '4',
            'volumeDonation' => '6',
        ];

        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getMarketInfo')
            ->withConsecutive(
                ['convertedmarket', 86400],
                ['convertedmarket', 2592000]
            )->willReturnOnConsecutiveCalls(
                $data,
                $dataMonth
            );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $this->mockUserManager(),
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket('FOO', 'BAR');
        $info = $mh->getMarketInfo($market);

        $this->assertEquals($data['last'], $info->getLast()->getAmount());
        $this->assertEquals($data['volume'] + $data['volumeDonation'], $info->getVolume()->getAmount());
        $this->assertEquals($data['open'], $info->getOpen()->getAmount());
        $this->assertEquals($data['close'], $info->getClose()->getAmount());
        $this->assertEquals($data['high'], $info->getHigh()->getAmount());
        $this->assertEquals($data['low'], $info->getLow()->getAmount());
        $this->assertEquals($data['deal'] + $data['dealDonation'], $info->getDeal()->getAmount());
        $this->assertEquals('FOO', $info->getCryptoSymbol());
        $this->assertEquals('BAR', $info->getTokenName());
        $this->assertEquals('0', $info->getBuyDepth()->getAmount());
        $this->assertEquals('6', $info->getMonthDeal()->getAmount());
    }

    public function testGetMarketInfoWithException(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getMarketInfo')
            ->with('convertedmarket')
            ->willReturn([]);

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $this->mockUserManager(),
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket('FOO', 'BAR');

        $this->expectException(InvalidArgumentException::class);

        $mh->getMarketInfo($market);
    }

    public function testGetPendingOrdersSummary(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrdersByUser')
        ->willReturn(
            $this->getPendingOrders()
        );
        $fetcher->method('getPendingOrders')
        ->willReturn(
            $this->getPendingOrders()
        );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturnCallback(function (int $id) {
            return $this->mockUser(2);
        });

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $this->mockBalanceHandler(),
            $this->mockContractHandler(),
            $this->mockParameterBag(),
            $this->limitHistoryConfigMock()
        );

        $market = $this->mockMarket('BASE', 'QUOTE');

        $totalSellOrders = $mh->getSellOrdersSummary($market, $this->mockUser(2))->getQuoteAmount();
        $totalBuyOrders  = $mh->getBuyOrdersSummary($market)->getBasePrice();

        $this->assertEquals('3', $totalSellOrders);
        $this->assertEquals('54', $totalBuyOrders);
    }

    public function testSoldOnMarket(): void
    {
        $token = $this->mockToken();
        $token->method('isCreatedOnMintmeSite')->willReturn(true);
        $token->method('getOwner')->willReturn($this->mockUser(1));
        $token->method('getWithdrawn')->willReturn($this->mockMoney('3'));
        $token->method('isDeployed')->willReturn(true);

        $pendingTokenWithdraw = $this->createMock(PendingTokenWithdraw::class);
        $pendingTokenWithdraw
            ->method('getToken')
            ->willReturn($token);
        $pendingTokenWithdraw
            ->method('getAmount')
            ->willReturn(new Amount(new Money(1, new Currency('TOK'))));

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('getValues')
            ->willReturn([$pendingTokenWithdraw]);

        $persistentCollection = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ClassMetadata::class),
            $collection
        );

        /** @var User|MockObject $owner */
        $owner = $token->getOwner();
        $owner->method('getPendingTokenWithdrawals')->willReturn($persistentCollection);

        /** @var MoneyWrapperInterface|MockObject $moneyWrapper */
        $moneyWrapper = $this->mockMoneyWrapper();
        $moneyWrapper
            ->method('parse')
            ->with('20', Symbols::TOK)
            ->willReturn(new Money(20, new Currency('TOK')));

        $parameterBag = $this->mockParameterBag();
        $parameterBag
            ->method('get')
            ->with('token_quantity')
            ->willReturn('20');

        $balanceResult = BalanceResult::success(
            new Money(5, new Currency('TOK')),
            new Money(2, new Currency('TOK')),
            new Money(0, new Currency('TOK'))
        );

        $balanceHandler = $this->mockBalanceHandler();
        $balanceHandler
            ->method('balance')
            ->willReturn($balanceResult);

        $contractHandler = $this->mockContractHandler();

        $contractHandler
            ->method('getPendingWithdrawals')
            ->willReturn([
                new Transaction(
                    new \DateTime('2050-01-01T15:03:01.012345Z'),
                    null,
                    null,
                    '123',
                    new Money(2, new Currency('TOK')),
                    null,
                    $token,
                    Status::fromString('pending'),
                    Type::fromString('withdraw')
                ),
            ]);

        $mh = new MarketHandler(
            $this->mockMarketFetcher(),
            $moneyWrapper,
            $this->mockUserManager(),
            $this->mockMarketNameConverter(),
            $this->mockDonationManager(),
            $this->mockMarketFactory(),
            $this->mockCryptoManager(),
            $balanceHandler,
            $contractHandler,
            $parameterBag,
            $this->limitHistoryConfigMock()
        );

        $soldOnMarket = $mh->soldOnMarket($token);

        $this->assertEquals('7', $soldOnMarket->getAmount());
    }

    private function getExecutedOrders(): array
    {
        return [
            ['maker_id'=>1, 'id'=>2, 'taker_id'=>3, 'amount'=>'4', 'type'=>'all', 'price'=>'6', 'time'=> 100, 'fee'=> 8],
            ['maker_id'=>1, 'id'=>2, 'taker_id'=>null, 'amount'=>'4', 'type'=>'sell', 'price'=>'6', 'time'=> 100, 'fee'=> 8],
            ['maker_id'=>1, 'id'=>2, 'taker_id'=>3, 'amount'=>'4', 'type'=>'buy', 'price'=>'6', 'time'=> 100, 'fee'=>null],
            ['maker_id'=>1, 'id'=>2, 'taker_id'=>3, 'amount'=>'4', 'type'=>'all', 'price'=>'6', 'time'=>null, 'fee'=>null],
        ];
    }

    private function getPendingOrders(): array
    {
        return [
            ['user'=>1,'id'=>2,'left'=>'3','side'=>0,'price'=>'6','mtime'=>7,'maker_fee'=>8.,'taker_fee'=>8.],
            ['user'=>1,'id'=>2,'left'=>'3','side'=>1,'price'=>'6','mtime'=>7,'maker_fee'=>8.,'taker_fee'=>8.],
            ['user'=>1,'id'=>2,'left'=>'3','side'=>2,'price'=>'6','mtime'=>7,'maker_fee'=>6.,'taker_fee'=>6.],
        ];
    }

    private function getDeals(): array
    {
        return [
            [
                'id' => 1,
                'time' => 3,
                'user' => 3,
                'side' => 0,
                'role' => 5,
                'amount' => '1',
                'price' => '2',
                'deal' => '3',
                'fee' => '4',
                'deal_order_id' => 123,
                'order_id' => 801,
                'market' => 'convertedmarket',
            ],
            [
                'id' => 2,
                'time' => 2,
                'user' => 3,
                'side' => 0,
                'role' => 5,
                'amount' => '1',
                'price' => '2',
                'deal' => '3',
                'fee' => '4',
                'deal_order_id' => 124,
                'order_id' => 802,
                'market' => 'convertedmarket',
            ],
        ];
    }

    private function getKlineStats(): array
    {
        return [
            [99999, '1', '2', '3', '4', '5', '6'],
            [99999, '7', '8', '9', '10', '11', '12'],
        ];
    }

    private function convertPending(array $orders): array
    {
        return array_map(function (Order $order) {
            return [
                'user' => $order->getMaker()->getId(),
                'id'=> $order->getId(),
                'left'=> $order->getAmount()->getAmount(),
                'side'=> $order->getSide(),
                'price'=> $order->getPrice()->getAmount(),
                'mtime'=> $order->getTimestamp(),
                'maker_fee'=> $order->getFee()->getAmount(),
                'taker_fee'=> $order->getFee()->getAmount(),
            ];
        }, $orders);
    }

    private function convertBuyPendingSummary(string $basePrice, string $quoteAmount): BuyOrdersSummaryResult
    {
        return new BuyOrdersSummaryResult($basePrice, $quoteAmount);
    }

    private function convertSellPendingSummary(string $baseAmount, string $quoteAmount): SellOrdersSummaryResult
    {
        return new SellOrdersSummaryResult($baseAmount, $quoteAmount);
    }

    private function mockBuySummary(string $basePrice, string $quoteAmount): BuyOrdersSummaryResult
    {
        $buySummary = $this->createMock(BuyOrdersSummaryResult::class);
        $buySummary->method('getBasePrice')->willReturn($basePrice);
        $buySummary->method('getQuoteAmount')->willReturn($quoteAmount);

        return $buySummary;
    }

    private function mockSellSummary(string $baseAmount, string $quoteAmount): SellOrdersSummaryResult
    {
        $sellSummary = $this->createMock(SellOrdersSummaryResult::class);
        $sellSummary->method('getBaseAmount')->willReturn($baseAmount);
        $sellSummary->method('getQuoteAmount')->willReturn($quoteAmount);

        return $sellSummary;
    }

    /** @return User|MockObject */
    private function mockUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    /** @return Market|MockObject */
    private function mockMarket(string $base = '', string $quote = '', bool $tokenMarket = false): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getQuote')->willReturn($this->mockTradable($quote, $tokenMarket));
        $market->method('getBase')->willReturn($this->mockTradable($base));

        return $market;
    }

    /** @return TradableInterface|MockObject */
    private function mockTradable(string $symbol, bool $isToken = false): TradableInterface
    {
        /** @var TradableInterface|MockObject $el */
        $el = $this->createMock($isToken ? Token::class : TradableInterface::class);
        $el->method('getSymbol')->willReturn($symbol);
        $el->method('getMoneySymbol')->willReturn($symbol);
        $el->method('getShowSubunit')->willReturn(4);

        return $el;
    }

    /** @return MarketFetcherInterface|MockObject */
    private function mockMarketFetcher(): MarketFetcherInterface
    {
        return $this->createMock(MarketFetcherInterface::class);
    }

    /** @return UserManagerInterface|MockObject */
    private function mockUserManager(): UserManagerInterface
    {
        return $this->createMock(UserManagerInterface::class);
    }

    /** @return MarketNameConverterInterface|MockObject */
    private function mockMarketNameConverter(): MarketNameConverterInterface
    {
        $converter = $this->createMock(MarketNameConverterInterface::class);
        $converter->method('convert')->willReturn('convertedmarket');

        return $converter;
    }

    private function mockDonationManager(): DonationManagerInterface
    {
        $donationManager = $this->createMock(DonationManagerInterface::class);
        $donationManager->method('getUserRelated')->willReturn([
            $this->mockDonation(),
        ]);

        return $donationManager;
    }

    /** @return MarketFactoryInterface|MockObject */
    private function mockMarketFactory(): MarketFactoryInterface
    {
        return $this->createMock(MarketFactoryInterface::class);
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager->method('findBySymbol')->willReturn($this->mockCrypto());

        return $cryptoManager;
    }

    /** @return BalanceHandlerInterface|MockObject */
    private function mockBalanceHandler(): BalanceHandlerInterface
    {
        return $this->createMock(BalanceHandlerInterface::class);
    }

    /** @return ContractHandlerInterface|MockObject */
    private function mockContractHandler(): ContractHandlerInterface
    {
        return $this->createMock(ContractHandlerInterface::class);
    }

    /** @return ParameterBagInterface|MockObject */
    private function mockParameterBag(): ParameterBagInterface
    {
        return $this->createMock(ParameterBagInterface::class);
    }

    private function mockDonation(): Donation
    {
        $donation = $this->createMock(Donation::class);
        $donation->method('getAmount')->willReturn($this->dummyMoneyObject());
        $donation->method('getFeeAmount')->willReturn($this->dummyMoneyObject());
        $donation->method('getReceiverAmount')->willReturn($this->dummyMoneyObject());
        $donation->method('getReceiverFeeAmount')->willReturn($this->dummyMoneyObject());
        $donation->method('getTokenAmount')->willReturn($this->dummyMoneyObject());
        $donation->method('getCurrency')->willReturn("TOK");
        $donation->method('getReceiverCurrency')->willReturn("TOK");
        $donation->method('getToken')->willReturn($this->mockToken());
        $donation->method('getDonor')->willReturn($this->mockUser(1));

        return $donation;
    }

    private function dummyMoneyObject(): Money
    {
        return new Money(1, new Currency('TOK'));
    }

    /** @return Token|MockObject */
    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockMoney(string $amount, string $currency = Symbols::TOK): Money
    {
        return new Money($amount, new Currency($currency));
    }

    private function limitHistoryConfigMock(): LimitHistoryConfig
    {
        $config = $this->createMock(LimitHistoryConfig::class);
        $date = (new \DateTimeImmutable())->setTimestamp(1);

        $config
            ->method('getFromDate')
            ->willReturn($date);

        return $config;
    }
}
