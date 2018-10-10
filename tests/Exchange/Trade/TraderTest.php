<?php

namespace App\Tests\Exchange\Trade;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Trader;
use App\Exchange\Trade\TradeResult;
use PHPUnit\Framework\TestCase;

class TraderTest extends TestCase
{
    /** @dataProvider placeOrderProvider */
    public function testPlaceOrder(bool $hasError, array $error, int $tradeResult): void
    {
        $method = 'order.put_limit';
        $params = [1, 'TOK000000000001WEB', 1, '10', '5', 0.01, 0.01, ''];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getError')->willReturn($error);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $trader = new Trader($jsonRpc, $this->createOrderConfig());
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
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new Trader($jsonRpc, $this->createMock(LimitOrderConfig::class));
        $this->assertEquals(
            TradeResult::FAILED,
            $trader->placeOrder($this->createMock(Order::class))->getResult()
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

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $trader = new Trader($jsonRpc, $this->createOrderConfig());
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
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new Trader($jsonRpc, $this->createMock(LimitOrderConfig::class));
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

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $trader = new Trader($jsonRpc, $this->createOrderConfig());
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
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new Trader($jsonRpc, $this->createMock(LimitOrderConfig::class));
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

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $trader = new Trader($jsonRpc, $this->createOrderConfig());
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
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new Trader($jsonRpc, $this->createMock(LimitOrderConfig::class));
        $this->assertEquals(
            [],
            $trader->getPendingOrders(
                $this->createUser(),
                $this->createMarket()
            )
        );
    }

    private function createOrderConfig(): LimitOrderConfig
    {
        $orderConfig = $this->createMock(LimitOrderConfig::class);
        $orderConfig->method('getTakerFeeRate')->willReturn(0.01);
        $orderConfig->method('getMakerFeeRate')->willReturn(0.01);
        return $orderConfig;
    }

    private function createOrder(): Order
    {
        $market = $this->createMarket();

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getMakerId')->willReturn(1);
        $order->method('getMarket')->willReturn($market);
        $order->method('getSide')->willReturn(1);
        $order->method('getAmount')->willReturn('10');
        $order->method('getPrice')->willReturn('5');
    
        return $order;
    }

    private function createUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        return $user;
    }

    private function createMarket(): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getHiddenName')->willReturn('TOK000000000001WEB');
        return $market;
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
                    'mtime' => 1492697636.238869,
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
                '10',
                1,
                '5',
                $status,
                1492697636
            ),
        ];
    }
}
