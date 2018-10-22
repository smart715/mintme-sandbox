<?php

namespace App\Tests\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Balance\Exception\BalanceException;
use App\Utils\RandomNumber;
use App\Utils\TokenNameConverterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BalanceHandlerTest extends TestCase
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

        $handler = new BalanceHandler(
            $rpc,
            $this->mockConverter(),
            $this->mockRandom(21)
        );

        $result = $handler->balance(
            $this->mockUser(1),
            $this->mockToken(999)
        );

        $this->assertFalse($result->isFailed());
        $this->assertEquals(1000000, $result->getAvailable());
        $this->assertEquals(100, $result->getFreeze());
    }

    public function testBalanceWithException(): void
    {
        $rpc = $this->mockRpc();
        $rpc->method('send')->willThrowException(new FetchException());

        $handler = new BalanceHandler(
            $rpc,
            $this->mockConverter(),
            $this->mockRandom(21)
        );

        $result = $handler->balance($this->mockUser(1), $this->mockToken(999));

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

        $handler = new BalanceHandler(
            $rpc,
            $this->mockConverter(),
            $this->mockRandom(21)
        );

        $result = $handler->summary($this->mockToken(999));

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

        $handler = new BalanceHandler(
            $rpc,
            $this->mockConverter(),
            $this->mockRandom(21)
        );

        $result = $handler->summary($this->mockToken(999));

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

        $handler = new BalanceHandler(
            $rpc,
            $this->mockConverter(),
            $this->mockRandom(21)
        );

        $result = $handler->summary($this->mockToken(999));

        $this->assertTrue($result->isFailed());
    }

    public function testBalanceUpdateThrowsException(): void
    {
        $rpc = $this->mockRpc();
        $rpc->method('send')->willReturn($this->mockResponse(true));

        $handler = new BalanceHandler(
            $rpc,
            $this->mockConverter(),
            $this->mockRandom(21)
        );

        $this->expectException(BalanceException::class);

        $handler->withdraw($this->mockUser(1), $this->mockToken(999), 1000000);
    }

    public function testWithdraw(): void
    {
        $rpc = $this->mockRpc();
        $rpc->expects($this->once())->method('send')->with(
            'balance.update',
            [ 1, 'TOK999', 'withdraw', 21, '1000000', [ 'extra' => 1 ] ]
        );

        $handler = new BalanceHandler(
            $rpc,
            $this->mockConverter(),
            $this->mockRandom(21)
        );

        $handler->withdraw($this->mockUser(1), $this->mockToken(999), 1000000);
    }

    public function testDeposit(): void
    {
        $rpc = $this->mockRpc();
        $rpc->expects($this->once())->method('send')->with(
            'balance.update',
            [ 1, 'TOK999', 'deposit', 21, '1000000', [ 'extra' => 1 ] ]
        );

        $handler = new BalanceHandler(
            $rpc,
            $this->mockConverter(),
            $this->mockRandom(21)
        );

        $handler->deposit($this->mockUser(1), $this->mockToken(999), 1000000);
    }

    /** @return MockObject|JsonRpcResponse */
    private function mockResponse(bool $value, array $result = []): JsonRpcResponse
    {
        $response = $this->createMock(JsonRpcResponse::class);
        $response->method('hasError')->willReturn($value);
        $response->method('getResult')->willReturn($result);

        return $response;
    }

    /** @return MockObject|Token */
    private function mockToken(int $value): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getId')->willReturn($value);

        return $token;
    }

    /** @return MockObject|User */
    private function mockUser(int $value): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($value);

        return $user;
    }

    /** @return MockObject|RandomNumber */
    private function mockRandom(int $value): RandomNumber
    {
        $random = $this->createMock(RandomNumber::class);
        $random->method('getNumber')->willReturn($value);

        return $random;
    }

    /** @return MockObject|TokenNameConverterInterface */
    private function mockConverter(): TokenNameConverterInterface
    {
        $converter = $this->createMock(TokenNameConverterInterface::class);
        $converter->method('convert')->willReturnCallback(function (Token $token) {
            return 'TOK'.$token->getId();
        });

        return $converter;
    }

    /** @return MockObject|JsonRpcInterface */
    private function mockRpc(): JsonRpcInterface
    {
        return $this->createMock(JsonRpcInterface::class);
    }
}
