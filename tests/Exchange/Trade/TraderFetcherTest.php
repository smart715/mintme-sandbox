<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Trade;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\User;
use App\Exchange\Config\Config;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Exchange\Trade\Trader;
use App\Exchange\Trade\TradeResult;
use App\Exchange\Trade\TraderFetcher;
use App\Repository\UserRepository;
use App\Utils\DateTimeInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TraderFetcherTest extends TestCase
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

        $trader = new TraderFetcher($jsonRpc, $this->mockConfig(0));

        $this->assertEquals(
            $tradeResult,
            $trader->placeOrder(...$this->getOrderData())->getResult()
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

        $trader = new TraderFetcher(
            $jsonRpc,
            $this->mockConfig(0)
        );

        $this->assertEquals(
            TradeResult::FAILED,
            $trader->placeOrder(...$this->getOrderData())->getResult()
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

        $trader = new TraderFetcher($jsonRpc, $this->mockConfig(0));

        $this->assertEquals(
            $tradeResult,
            $trader->cancelOrder(...$params)->getResult()
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
        $params = [1, 'TOK000000000001WEB', 1];
        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new TraderFetcher($jsonRpc, $this->mockConfig(0));

        $this->assertEquals(
            TradeResult::FAILED,
            $trader->cancelOrder(...$params)->getResult()
        );
    }

    /** @dataProvider finishedOrdersProvider */
    public function testGetFinishedOrders(
        bool $hasError,
        array $error,
        ?array $result,
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

        $trader = new TraderFetcher($jsonRpc, $this->mockConfig(0));

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $this->assertEquals(
            $finishedOrders,
            $trader->getFinishedOrders(...$params)
        );
    }

    public function finishedOrdersProvider(): array
    {
        return [
            [false, [], $this->getRawOrders(), $this->getOrders()],
            [true, ['code' => 1, 'message' => 'invalid arguments'], null, []],
        ];
    }

    public function testGetFinishedOrdersException(): void
    {
        $params = [1, 'TOK000000000001WEB', 0, 0, 100, 100, 1];
        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new TraderFetcher($jsonRpc, $this->mockConfig(0));

        $this->expectException(FetchException::class);

        $trader->getFinishedOrders(...$params);
    }

    /** @dataProvider pendingOrdersProvider */
    public function testGetPendingOrders(
        bool $hasError,
        array $error,
        ?array $result,
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

        $trader = new TraderFetcher($jsonRpc, $this->mockConfig(0));

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $this->assertEquals(
            $pendingOrders,
            $trader->getPendingOrders(...$params)
        );
    }

    public function pendingOrdersProvider(): array
    {
        return [
            [false, [], $this->getRawOrders(), $this->getOrders()],
            [true, ['code' => 1, 'message' => 'invalid arguments'], null, []],
        ];
    }

    public function testGetPendingOrdersException(): void
    {
        $params = [1, 'TOK000000000001WEB', 100, 100, 1];
        /** @var MockObject|JsonRpcInterface $jsonRpc */
        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->will($this->throwException(new FetchException()));

        $trader = new TraderFetcher($jsonRpc, $this->mockConfig(0));
        $this->expectException(FetchException::class);

        $trader->getPendingOrders(...$params);
    }

    private function getOrderData(): array
    {
        return [1, 'TOK000000000001WEB', 1, '10', '5', '0.01', '0.01', 0, '0'];
    }

    /** @return Config|MockObject */
    private function mockConfig(int $offset): Config
    {
        $config = $this->createMock(Config::class);

        $config->method('getOffset')->willReturn($offset);

        return $config;
    }

    private function getRawOrders(): array
    {
        return [
            'offset' => 100,
            'limit' => 100,
            'total' => 1,
            'records' => $this->getOrders(),
        ];
    }

    private function getOrders(): array
    {
        return [[
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
        ]];
    }
}
