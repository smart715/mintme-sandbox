<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Config\Config;
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
    /** @dataProvider marketInfoProvider */
    public function testGetMarketInfo(bool $hasError, ?array $rpcResult): void
    {
        $method = 'market.status';
        $params = ["TOK000000000001WEB", 86400];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult')->willReturn($rpcResult);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $marketFetcher = new MarketFetcher($jsonRpc, $this->mockConfig(0));

        $this->assertEquals(
            $rpcResult,
            $marketFetcher->getMarketInfo('TOK000000000001WEB', 86400)
        );
    }

    public function marketInfoProvider(): array
    {
        return [
            [false, $this->getMarketInfoResult()],
        ];
    }

    /** @dataProvider pendingSellOrdersProvider */
    public function testGetPendingSellOrders(bool $hasError, ?array $rpcResult): void
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

        $marketFetcher = new MarketFetcher($jsonRpc, $this->mockConfig(0));

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $this->assertEquals(
            $rpcResult['orders'],
            $marketFetcher->getPendingOrders('TOK000000000001WEB', 0, 100, MarketFetcher::SELL)
        );
    }

    public function pendingSellOrdersProvider(): array
    {
        return [
            [false, $this->getPendingResult(Order::SELL_SIDE)],
            [true, null, []],
        ];
    }

    public function testGetPendingSellOrdersException(): void
    {
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $marketFetcher = new MarketFetcher($jsonRpc, $this->mockConfig(0));
        $this->expectException(FetchException::class);
        $this->assertEquals(
            [],
            $marketFetcher->getPendingOrders('TOK000000000001WEB', 0, 100, MarketFetcher::SELL)
        );
    }

    /** @dataProvider pendingBuyOrdersProvider */
    public function testGetPendingBuyOrders(bool $hasError, ?array $rpcResult): void
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

        $marketFetcher = new MarketFetcher($jsonRpc, $this->mockConfig(0));

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $this->assertEquals(
            $rpcResult['orders'],
            $marketFetcher->getPendingOrders('TOK000000000001WEB', 0, 100, MarketFetcher::BUY)
        );
    }

    public function pendingBuyOrdersProvider(): array
    {
        return [
            [false, $this->getPendingResult(Order::BUY_SIDE)],
            [true, null],
        ];
    }

    public function testGetPendingBuyOrdersException(): void
    {
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $marketFetcher = new MarketFetcher($jsonRpc, $this->mockConfig(0));
        $this->expectException(FetchException::class);
        $this->assertEquals(
            [],
            $marketFetcher->getPendingOrders('TOK000000000001WEB', 0, 100, MarketFetcher::BUY)
        );
    }

    /** @dataProvider executedOrdersProvider */
    public function testGetExecutedOrders(bool $hasError, ?array $rpcResult): void
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

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $marketFetcher = new MarketFetcher($jsonRpc, $this->mockConfig(0));
        $this->assertEquals(
            $rpcResult,
            $marketFetcher->getExecutedOrders('TOK000000000001WEB')
        );
    }

    public function executedOrdersProvider(): array
    {
        return [
            [false, $this->getExecutedResult(), $this->getExecutedOrders()],
            [true, null, []],
        ];
    }

    public function testGetExecutedOrdersException(): void
    {
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $marketFetcher = new MarketFetcher($jsonRpc, $this->mockConfig(0));
        $this->expectException(FetchException::class);
        $this->assertEquals(
            null,
            $marketFetcher->getExecutedOrders('TOK000000000001WEB')
        );
    }

    /** @return Config|MockObject */
    private function mockConfig(int $offset): Config
    {
        $config = $this->createMock(Config::class);

        $config->method('getOffset')->willReturn($offset);

        return $config;
    }

    /** @return MockObject|Market */
    private function createMarket(): Market
    {
        $market = $this->createMock(Market::class);

        $token = $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn(MoneyWrapper::TOK_SYMBOL);

        $market->method('getQuote')->willReturn($token);

        return $market;
    }

    private function createMoney(int $value): Money
    {
        return new Money($value, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    private function getMarketInfoResult(): array
    {
        return [
            'result' => [
                'period' => 86400,
                'last' => '2',
                'open' => '5',
                'close' => '2',
                'high' => '5',
                'low' => '2',
                'volume' => '1.98',
                'deal' => '6.93',
            ],
        ];
    }

    private function getPendingResult(int $side): array
    {
        return [
            'orders' => [
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
                'maker_id' => 1,
                'taker_id' => 2,
            ],
        ];
    }

    private function getExecutedOrders(): array
    {
        return [
            new Order(
                1,
                $this->createMock(User::class),
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
