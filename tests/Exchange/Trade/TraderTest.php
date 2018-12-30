<?php

namespace App\Tests\Exchange\Trade;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Exchange\Trade\Trader;
use App\Exchange\Trade\TradeResult;
use App\Repository\UserRepository;
use App\Utils\DateTimeInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TraderTest extends TestCase
{
    /** @dataProvider placeOrderProvider */
    public function testPlaceOrder(bool $hasError, array $error, int $tradeResult): void
    {
        $method = 'order.put_limit';
        $params = [1, 'TOK000000000001WEB', 1, '10', '5', '0.01', '0.01', '', 0, '0'];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getError')->willReturn($error);

        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $trader = new Trader(
            $jsonRpc,
            $this->createOrderConfig(),
            $this->mockEntityManager(),
            $this->createMoneyWrapper(),
            $this->createPrelaunchConfig(false, new \DateTimeImmutable()),
            $this->createAppDateTime(new \DateTimeImmutable())
        );

        $this->assertEquals(
            $tradeResult,
            $trader->placeOrder($this->createOrder())->getResult()
        );
    }

    public function placeOrderProvider(): array
    {
        return [
            [false, [], TradeResult::SUCCESS],
            [true, ['code' => 1, 'message' => 'invalid arguments'], TradeResult::FAILED],
            [true, ['code' => 10, 'message' => 'balance not enough'], TradeResult::INSUFFICIENT_BALANCE],
        ];
    }

    public function testPlaceOrderException(): void
    {
        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new Trader(
            $jsonRpc,
            $this->createMock(LimitOrderConfig::class),
            $this->mockEntityManager(),
            $this->createMoneyWrapper(),
            $this->createPrelaunchConfig(false, new \DateTimeImmutable()),
            $this->createAppDateTime(new \DateTimeImmutable())
        );

        $this->assertEquals(
            TradeResult::FAILED,
            $trader->placeOrder($this->createOrder())->getResult()
        );
    }

    /** @dataProvider cancelOrderProvider */
    public function testCancelOrder(bool $hasError, array $error, int $tradeResult): void
    {
        $method = 'order.cancel';
        $params = [1, 'TOK000000000001WEB', 1];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getError')->willReturn($error);

        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $trader = new Trader(
            $jsonRpc,
            $this->createOrderConfig(),
            $this->mockEntityManager(),
            $this->createMoneyWrapper(),
            $this->createPrelaunchConfig(false, new \DateTimeImmutable()),
            $this->createAppDateTime(new \DateTimeImmutable())
        );

        $this->assertEquals(
            $tradeResult,
            $trader->cancelOrder($this->createOrder())->getResult()
        );
    }

    public function cancelOrderProvider(): array
    {
        return [
            [false, [], TradeResult::SUCCESS],
            [true, ['code' => 1, 'message' => 'invalid arguments'], TradeResult::FAILED],
            [true, ['code' => 10, 'message' => 'order not found'], TradeResult::ORDER_NOT_FOUND],
            [true, ['code' => 11, 'message' => 'user not match'], TradeResult::USER_NOT_MATCH],
        ];
    }

    public function testCancelOrderException(): void
    {
        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new Trader(
            $jsonRpc,
            $this->createMock(LimitOrderConfig::class),
            $this->mockEntityManager(),
            $this->createMoneyWrapper(),
            $this->createPrelaunchConfig(false, new \DateTimeImmutable()),
            $this->createAppDateTime(new \DateTimeImmutable())
        );

        $this->assertEquals(
            TradeResult::FAILED,
            $trader->cancelOrder($this->createMock(Order::class))->getResult()
        );
    }

    /** @dataProvider finishedOrdersProvider */
    public function testGetFinishedOrders(
        bool $hasError,
        array $error,
        array $result,
        array $finishedOrders
    ): void {
        $method = 'order.finished';
        $params = [1, 'TOK000000000001WEB', 0, 0, 100, 100, 1];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getError')->willReturn($error);
        $jsonResponse->method('getResult')->willReturn($result);

        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $trader = new Trader(
            $jsonRpc,
            $this->createOrderConfig(),
            $this->mockEntityManager(),
            $this->createMoneyWrapper(),
            $this->createPrelaunchConfig(false, new \DateTimeImmutable()),
            $this->createAppDateTime(new \DateTimeImmutable())
        );

        $this->assertEquals(
            $finishedOrders,
            $trader->getFinishedOrders(
                $this->createUser(),
                $this->createMarket(),
                ['offset' => 100, 'side' => 'sell']
            )
        );
    }

    public function finishedOrdersProvider(): array
    {
        return [
            [false, [], $this->getRawOrders(), $this->getOrders(Order::FINISHED_STATUS)],
            [true, ['code' => 1, 'message' => 'invalid arguments'], [], []],
        ];
    }

    public function testGetFinishedOrdersException(): void
    {
        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new Trader(
            $jsonRpc,
            $this->createMock(LimitOrderConfig::class),
            $this->mockEntityManager(),
            $this->createMoneyWrapper(),
            $this->createPrelaunchConfig(false, new \DateTimeImmutable()),
            $this->createAppDateTime(new \DateTimeImmutable())
        );

        $this->assertEquals(
            [],
            $trader->getFinishedOrders(
                $this->createUser(),
                $this->createMarket()
            )
        );
    }

    /** @dataProvider pendingOrdersProvider */
    public function testGetPendingOrders(
        bool $hasError,
        array $error,
        array $result,
        array $pendingOrders
    ): void {
        $method = 'order.pending';
        $params = [1, 'TOK000000000001WEB', 100, 100, 1];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getError')->willReturn($error);
        $jsonResponse->method('getResult')->willReturn($result);

        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $trader = new Trader(
            $jsonRpc,
            $this->createOrderConfig(),
            $this->mockEntityManager(),
            $this->createMoneyWrapper(),
            $this->createPrelaunchConfig(false, new \DateTimeImmutable()),
            $this->createAppDateTime(new \DateTimeImmutable())
        );

        $this->assertEquals(
            $pendingOrders,
            $trader->getPendingOrders(
                $this->createUser(),
                $this->createMarket(),
                ['offset' => 100, 'side' => 'sell']
            )
        );
    }

    public function pendingOrdersProvider(): array
    {
        return [
            [false, [], $this->getRawOrders(), $this->getOrders(Order::PENDING_STATUS)],
            [true, ['code' => 1, 'message' => 'invalid arguments'], [], []],
        ];
    }

    public function testGetPendingOrdersException(): void
    {
        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new Trader(
            $jsonRpc,
            $this->createMock(LimitOrderConfig::class),
            $this->mockEntityManager(),
            $this->createMoneyWrapper(),
            $this->createPrelaunchConfig(false, new \DateTimeImmutable()),
            $this->createAppDateTime(new \DateTimeImmutable())
        );
        $this->assertEquals(
            [],
            $trader->getPendingOrders(
                $this->createUser(),
                $this->createMarket()
            )
        );
    }

    /** @return MockObject|DateTimeInterface */
    private function createAppDateTime(\DateTimeInterface $dateTime): DateTimeInterface
    {
        $time = $this->createMock(DateTimeInterface::class);

        $time->method('now')->willReturn($dateTime);

        return $time;
    }

    /** @return MockObject|PrelaunchConfig */
    private function createPrelaunchConfig(bool $enabled, \DateTimeInterface $dateTime): PrelaunchConfig
    {
        $config = $this->createMock(PrelaunchConfig::class);

        $config->method('isEnabled')->willReturn($enabled);
        $config->method('getTradeFinishDate')->willReturn($dateTime);

        return $config;
    }

    /** @return MockObject|MoneyWrapperInterface */
    private function createMoneyWrapper(): MoneyWrapperInterface
    {
        $wrapper = $this->createMock(MoneyWrapperInterface::class);

        $wrapper->method('format')->willReturnCallback(function (Money $money) {
            return $money->getAmount();
        });

        return $wrapper;
    }

    /** @return MockObject|EntityManagerInterface */
    private function mockEntityManager(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->method('getRepository')->with(User::class)->willReturn($this->mockUserRepo());

        return $em;
    }

    /** @return MockObject|UserRepository */
    private function mockUserRepo(): UserRepository
    {
        return $this->createMock(UserRepository::class);
    }

    /** @return MockObject|LimitOrderConfig */
    private function createOrderConfig(): LimitOrderConfig
    {
        $orderConfig = $this->createMock(LimitOrderConfig::class);
        $orderConfig->method('getTakerFeeRate')->willReturn(0.01);
        $orderConfig->method('getMakerFeeRate')->willReturn(0.01);
        return $orderConfig;
    }

    /** @return MockObject|Order */
    private function createOrder(): Order
    {
        $market = $this->createMarket();

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getMakerId')->willReturn(1);
        $order->method('getMarket')->willReturn($market);
        $order->method('getSide')->willReturn(1);
        $order->method('getAmount')->willReturn($this->createMoney(10));
        $order->method('getPrice')->willReturn($this->createMoney(5));
    
        return $order;
    }

    /** @return MockObject|User */
    private function createUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        return $user;
    }

    /** @return MockObject|Market */
    private function createMarket(): Market
    {
        $market = $this->createMock(Market::class);

        $market->method('getHiddenName')->willReturn('TOK000000000001WEB');
        $market->method('getCurrencySymbol')->willReturn(MoneyWrapper::TOK_SYMBOL);

        return $market;
    }

    /**
     * Can't mock final class
     *
     * @return Money
     */
    private function createMoney(int $amount): Money
    {
        return new Money($amount, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    private function getRawOrders(): array
    {
        return [
            'offset' => 100,
            'limit' => 100,
            'total' => 1,
            'records' => [
                [
                    'id' => 2,
                    'ctime' => 1492616173.355293,
                    'mtime' => 1492697636.0,
                    'market' => 'TOK000000000001WEB',
                    'user' => 1,
                    'type' => 1,
                    'side' => 1,
                    'amount' => '10',
                    'price' => '5',
                    'taker_fee' => '0.01',
                    'maker_fee' => '0.01',
                    'source' => '',
                    'deal_money' => '6300.0000000000',
                    'deal_stock' => '0.9000000000',
                    'deal_fee' => '0.0009000000',
                ],
            ],
        ];
    }

    private function getOrders(string $status): array
    {
        return [
            new Order(
                2,
                1,
                null,
                $this->createMarket(),
                $this->createMoney(10),
                1,
                $this->createMoney(5),
                $status,
                1492697636
            ),
        ];
    }
}
