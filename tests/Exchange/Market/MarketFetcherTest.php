<?php

namespace App\Tests\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcher;
use App\Exchange\Order;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketFetcherTest extends TestCase
{
    /** @dataProvider pendingSellOrdersProvider */
    public function testGetPendingSellOrders(bool $hasError, array $rpcResult, array $sellOrders): void
    {
        $method = 'order.book';
        $params = ['TOK000000000001WEB', 1, 0, 100];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult')->willReturn($rpcResult);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $marketFetcher = new MarketFetcher($jsonRpc);
        $this->assertEquals(
            $rpcResult,
            $marketFetcher->getPendingOrders('TOK000000000001WEB', 0, 100, MarketFetcher::SELL)
        );
    }

    public function pendingSellOrdersProvider(): array
    {
        return [
            [false, $this->getPendingResult(Order::SELL_SIDE), $this->getPendingOrders(Order::SELL_SIDE)],
            [true, [], []],
        ];
    }

    public function testGetPendingSellOrdersException(): void
    {
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $marketFetcher = new MarketFetcher($jsonRpc);
        $this->assertEquals(
            [],
            $marketFetcher->getPendingOrders('TOK000000000001WEB', 0, 100, MarketFetcher::SELL)
        );
    }

    /** @dataProvider pendingBuyOrdersProvider */
    public function testGetPendingBuyOrders(bool $hasError, array $rpcResult, array $buyOrders): void
    {
        $method = 'order.book';
        $params = ['TOK000000000001WEB', 2, 0, 100];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult')->willReturn($rpcResult);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $marketFetcher = new MarketFetcher($jsonRpc);
        $this->assertEquals(
            $rpcResult,
            $marketFetcher->getPendingOrders('TOK000000000001WEB', 0, 100, MarketFetcher::BUY)
        );
    }

    public function pendingBuyOrdersProvider(): array
    {
        return [
            [false, $this->getPendingResult(Order::BUY_SIDE), $this->getPendingOrders(Order::BUY_SIDE)],
            [true, [], []],
        ];
    }

    public function testGetPendingBuyOrdersException(): void
    {
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $marketFetcher = new MarketFetcher($jsonRpc);
        $this->assertEquals(
            [],
            $marketFetcher->getPendingOrders('TOK000000000001WEB', 0, 100, MarketFetcher::BUY)
        );
    }

    /** @dataProvider executedOrdersProvider */
    public function testGetExecutedOrders(bool $hasError, array $rpcResult, array $orders): void
    {
        $method = 'market.deals';
        $params = ['TOK000000000001WEB', 100, 0];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult')->willReturn($rpcResult);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $marketFetcher = new MarketFetcher($jsonRpc);
        $this->assertEquals(
            $rpcResult,
            $marketFetcher->getExecutedOrders('TOK000000000001WEB')
        );
    }

    public function executedOrdersProvider(): array
    {
        return [
            [false, $this->getExecutedResult(), $this->getExecutedOrders()],
            [true, [], []],
        ];
    }

    public function testGetExecutedOrdersException(): void
    {
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $marketFetcher = new MarketFetcher($jsonRpc);
        $this->assertEquals(
            [],
            $marketFetcher->getExecutedOrders('TOK000000000001WEB')
        );
    }

    /** @return MockObject|Market */
    private function createMarket(): Market
    {
        $market = $this->createMock(Market::class);

        $market->method('getHiddenName')->willReturn('TOK000000000001WEB');
        $market->method('getCurrencySymbol')->willReturn(MoneyWrapper::TOK_SYMBOL);

        return $market;
    }

    private function createMoney(int $value): Money
    {
        return new Money($value, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    private function getPendingResult(int $side): array
    {
        return [
            [
                'id' => 1,
                'type' => 1,
                'side' => $side,
                'ctime' => 1492616173.355293,
                'mtime' => 1492697636.0,
                'user' => 1,
                'market' => 'TOK000000000001WEB',
                'price' => '1',
                'amount' => '10',
                'taker_fee' => '0.01',
                'maker_fee' => '0.01',
                'left' => '1',
                'freeze' => '0',
                'deal_stock' => '1',
                'deal_money' => '1',
                'deal_fee' => '0.01',
            ],
        ];
    }

    private function getPendingOrders(int $side): array
    {
        return [
            [
                [],
                1,
                1,
                null,
                $this->createMarket(),
                $this->createMoney(10),
                $side,
                $this->createMoney(1),
                Order::PENDING_STATUS,
                1492697636,
            ],
        ];
    }

    private function getExecutedResult(): array
    {
        return [
            [
                'id' => 1,
                'time' => 1492697636.0,
                'type' => 'sell',
                'amount' => '10',
                'price' => '1',
                'maker' => 1,
                'taker' => 2,
            ],
        ];
    }

    private function getExecutedOrders(): array
    {
        return [
            new Order(
                1,
                null,
                null,
                $this->createMarket(),
                $this->createMoney(10),
                1,
                $this->createMoney(1),
                Order::FINISHED_STATUS,
                1492697636
            ),
        ];
    }
}
