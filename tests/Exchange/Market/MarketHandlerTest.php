<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Market;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcherInterface;
use App\Exchange\Market\MarketHandler;
use App\Exchange\Market\Model\LineStat;
use App\Exchange\Order;
use App\Manager\UserManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

class MarketHandlerTest extends TestCase
{
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
            $this->mockMarketNameConverter()
        );

        $order = $mh->getExecutedOrder(
            $this->mockMarket('FOO', 'FOO'),
            2
        );

        $this->assertEquals(2, $order->getId());
    }

    public function testGetExecutedOrderWithException(): void
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
            $this->mockMarketNameConverter()
        );

        $this->expectException(Throwable::class);
        $mh->getExecutedOrder(
            $this->mockMarket('FOO', 'FOO'),
            123
        );
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
            $this->mockMarketNameConverter()
        );

        $orders = $mh->getExecutedOrders(
            $this->mockMarket('FOO', 'FOO', true),
            2
        );

        $this->assertEquals(array_map(function (array $row) {
            $row['fee'] = $row['fee'] ?? 0.;
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
                'fee' => $order->getFee(),
            ];
        }, $orders));
    }

    public function testGetExecutedOrdersWithException(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getExecutedOrders')
            ->with('convertedmarket', 2, 100)
            ->willReturn(
                $this->getExecutedOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturnCallback(function () {
            return null;
        });

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter()
        );

        $this->expectException(InvalidArgumentException::class);
        $mh->getExecutedOrders(
            $this->mockMarket('FOO', 'FOO', true),
            2
        );
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
            $this->mockMarketNameConverter()
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
            'maker_fee'=> $order->getFee(),
            'taker_fee'=> $order->getFee(),
        ]);
    }

    public function testGetPendingOrders(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrders')
            ->with('convertedmarket', 4, 100)
            ->willReturn(
                ['orders' => $this->getPendingOrders()]
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturnCallback(function (int $id) {
            return $this->mockUser($id);
        });

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter()
        );

        $buyOrders = $mh->getPendingBuyOrders(
            $this->mockMarket('FOO', 'BAR'),
            4
        );

        $sellOrders = $mh->getPendingSellOrders(
            $this->mockMarket('FOO', 'BAR'),
            4
        );

        $this->assertEquals($this->getPendingOrders(), $this->convertPending($buyOrders));
        $this->assertEquals($this->getPendingOrders(), $this->convertPending($sellOrders));
    }

    public function testGetPendingOrdersByUser(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getPendingOrdersByUser')
            ->with(1, 'convertedmarket', 4, 100)
            ->willReturn(
                $this->getPendingOrders()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturnCallback(function (int $id) {
            return $this->mockUser($id);
        });

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter()
        );

        $orders = $mh->getPendingOrdersByUser(
            $this->mockUser(1),
            [$this->mockMarket('FOO', 'BAR')],
            4
        );

        $this->assertEquals($this->getPendingOrders(), array_map(function (Order $order) {
            return [
                'user' => $order->getMaker()->getId(),
                'id'=> $order->getId(),
                'left'=> $order->getAmount()->getAmount(),
                'side'=> $order->getSide(),
                'price'=> $order->getPrice()->getAmount(),
                'mtime'=> $order->getTimestamp(),
                'maker_fee'=> $order->getFee(),
                'taker_fee'=> $order->getFee(),
            ];
        }, $orders));
    }

    public function testGetUserExecutedHistory(): void
    {
        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getUserExecutedHistory')
            ->with(1, 'convertedmarket', 0, 100)
            ->willReturn(
                $this->getDeals()
            );

        $userManager = $this->mockUserManager();
        $userManager->method('find')->willReturn(
            $this->mockUser(2)
        );

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $userManager,
            $this->mockMarketNameConverter()
        );

        $deals = $mh->getUserExecutedHistory(
            $this->mockUser(1),
            [
                $this->mockMarket('FOO', 'BAR'),
                $this->mockMarket('BAZ', 'QUX', true),
            ]
        );

        $this->assertEquals([$this->getDeals()[0], $this->getDeals()[0]], array_map(function (Deal $deal) {
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
            ];
        }, $deals));
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
            $this->mockMarketNameConverter()
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
        ];

        $fetcher = $this->mockMarketFetcher();
        $fetcher->method('getMarketInfo')
            ->with('convertedmarket')
            ->willReturn($data);

        $mh = new MarketHandler(
            $fetcher,
            $this->mockMoneyWrapper(),
            $this->mockUserManager(),
            $this->mockMarketNameConverter()
        );

        $market = $this->mockMarket('FOO', 'BAR');
        $info = $mh->getMarketInfo($market);

        $this->assertEquals($data, [
            'last' => $info->getLast()->getAmount(),
            'volume' => $info->getVolume()->getAmount(),
            'open' => $info->getOpen()->getAmount(),
            'close' => $info->getClose()->getAmount(),
            'high' => $info->getHigh()->getAmount(),
            'low' => $info->getLow()->getAmount(),
            'deal' => $info->getDeal()->getAmount(),
        ]);
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
            $this->mockMarketNameConverter()
        );

        $market = $this->mockMarket('FOO', 'BAR');

        $this->expectException(InvalidArgumentException::class);

        $mh->getMarketInfo($market);
    }

    private function getExecutedOrders(): array
    {
        return [
            ['maker_id'=>1, 'id'=>2, 'taker_id'=>3, 'amount'=>'4', 'type'=>'all', 'price'=>'6', 'time'=>7, 'fee'=>8.],
            ['maker_id'=>1, 'id'=>2, 'taker_id'=>null, 'amount'=>'4', 'type'=>'sell', 'price'=>'6', 'time'=>7, 'fee'=>8.],
            ['maker_id'=>1, 'id'=>2, 'taker_id'=>3, 'amount'=>'4', 'type'=>'buy', 'price'=>'6', 'time'=>7, 'fee'=>null],
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
            ['id' => 1, 'time' => 2, 'user' => 3, 'side' => 0, 'role' => 5, 'amount' => '1', 'price' => '2', 'deal' => '3', 'fee' => '4', 'deal_order_id' => 123],
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
                'maker_fee'=> $order->getFee(),
                'taker_fee'=> $order->getFee(),
            ];
        }, $orders);
    }

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
        $market->method('getQuote')->willReturn($this->mockTradeble($quote, $tokenMarket));
        $market->method('getBase')->willReturn($this->mockTradeble($base));

        return $market;
    }

    /** @return TradebleInterface|MockObject */
    private function mockTradeble(string $symbol, bool $isToken = false): TradebleInterface
    {
        /** @var TradebleInterface|MockObject $el */
        $el = $this->createMock($isToken ? Token::class : TradebleInterface::class);
        $el->method('getSymbol')->willReturn($symbol);

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

    /** @return MoneyWrapperInterface|MockObject */
    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->method('parse')->willReturnCallback(function (string $amount, string $symbol): Money {
            return new Money($amount, new Currency($symbol));
        });

        return $mw;
    }
}
