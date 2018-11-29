<?php

namespace App\Tests\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceFetcher;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Balance\Exception\BalanceException;
use App\Utils\RandomNumber;
use App\Utils\TokenNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BalanceFetcherTest extends TestCase
{
    public function testBalance(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'balance.query',
                [ 1, 'TOK999' ]
            )
            ->willReturn($this->mockResponse(false, [
                'TOK999' => [
                    'available' => '1000000',
                    'freeze' => '100',
                ],
            ]));

        $handler = new BalanceFetcher(
            $rpc,
            $this->mockRandom(21),
            $this->mockMoneyWrapper()
        );

        $result = $handler->balance(
            1,
            ['TOK999']
        )->get('TOK999');

        $this->assertFalse($result->isFailed());
        $this->assertEquals('1000000', $result->getAvailable()->getAmount());
        $this->assertEquals('100', $result->getFreeze()->getAmount());
    }

    public function testBalanceWithException(): void
    {
        $rpc = $this->mockRpc();
        $rpc->method('send')->willThrowException(new FetchException());

        $handler = new BalanceFetcher(
            $rpc,
            $this->mockRandom(21),
            $this->mockMoneyWrapper()
        );

        $result = $handler->balance(1, ['TOK999'])->get('TOK999');

        $this->assertTrue($result->isFailed());
    }

    public function testSummary(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'asset.summary',
                [ 'TOK999' ]
            )
            ->willReturn($this->mockResponse(false, [
                'name' => 'Foo',
                'total_balance' => '1000000',
                'available_balance' => 500000,
                'available_count' => 10,
                'freeze_balance' => 500000,
                'freeze_count' => 10,
            ]));

        $handler = new BalanceFetcher(
            $rpc,
            $this->mockRandom(21),
            $this->mockMoneyWrapper()
        );

        $result = $handler->summary('TOK999');

        $this->assertFalse($result->isFailed());
        $this->assertEquals(1000000, $result->getTotal());
        $this->assertEquals(500000, $result->getAvailable());
        $this->assertEquals(10, $result->getAvailableCount());
        $this->assertEquals(500000, $result->getFreeze());
        $this->assertEquals(10, $result->getFreezeCount());
    }

    public function testSummaryWithException(): void
    {
        $rpc = $this->mockRpc();
        $rpc->method('send')->willThrowException(new FetchException());

        $handler = new BalanceFetcher(
            $rpc,
            $this->mockRandom(21),
            $this->mockMoneyWrapper()
        );

        $result = $handler->summary('TOK999');

        $this->assertTrue($result->isFailed());
    }

    public function testSummaryWithError(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'asset.summary',
                [ 'TOK999' ]
            )
            ->willReturn($this->mockResponse(true));

        $handler = new BalanceFetcher(
            $rpc,
            $this->mockRandom(21),
            $this->mockMoneyWrapper()
        );

        $result = $handler->summary('TOK999');

        $this->assertTrue($result->isFailed());
    }

    public function testBalanceUpdateThrowsException(): void
    {
        $rpc = $this->mockRpc();
        $rpc->method('send')->willReturn($this->mockResponse(true));

        $handler = new BalanceFetcher(
            $rpc,
            $this->mockRandom(21),
            $this->mockMoneyWrapper()
        );

        $this->expectException(BalanceException::class);

        $handler->update(1, 'TOK999', '1000000', 'withdraw');
    }

    public function testUpdate(): void
    {
        $rpc = $this->mockRpc();
        $rpc->expects($this->once())->method('send')->with(
            'balance.update',
            [ 1, 'TOK999', 'withdraw', 21, '1000000', [ 'extra' => 1 ] ]
        );

        $handler = new BalanceFetcher(
            $rpc,
            $this->mockRandom(21),
            $this->mockMoneyWrapper()
        );

        $handler->update(1, 'TOK999', '1000000', 'withdraw');
    }

    /** @return MockObject|MoneyWrapperInterface */
    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $wrapper = $this->createMock(MoneyWrapperInterface::class);

        $wrapper->method('parse')->willReturnCallback(function (string $amount, string $symbol) {
            return new Money($amount, new Currency($symbol));
        });

        return $wrapper;
    }

    /** @return MockObject|JsonRpcResponse */
    private function mockResponse(bool $value, array $result = []): JsonRpcResponse
    {
        $response = $this->createMock(JsonRpcResponse::class);
        $response->method('hasError')->willReturn($value);
        $response->method('getResult')->willReturn($result);

        return $response;
    }

    /** @return MockObject|RandomNumber */
    private function mockRandom(int $value): RandomNumber
    {
        $random = $this->createMock(RandomNumber::class);
        $random->method('getNumber')->willReturn($value);

        return $random;
    }

    /** @return MockObject|JsonRpcInterface */
    private function mockRpc(): JsonRpcInterface
    {
        return $this->createMock(JsonRpcInterface::class);
    }
}