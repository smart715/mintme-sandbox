<?php declare(strict_types = 1);

namespace App\Tests\Withdraw\Fetcher;

use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Wallet\Withdraw\Fetcher\Storage\WithdrawStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WithdrawStorageTest extends TestCase
{
    public function testRequest(): void
    {
        /** @var JsonRpcInterface|MockObject $rpc */
        $rpc = $this->createMock(JsonRpcInterface::class);
        /** @var JsonRpcResponse|MockObject $rpc */
        $response = $this->createMock(JsonRpcResponse::class);
        $response->method('getResult')->willReturn(['balance' => '99']);

        $rpc->method('send')->willReturn($response);

        $storage = new WithdrawStorage($rpc, 'mintme');

        $this->assertEquals(['balance' => 99], $storage->requestHistory(1, 0, 10));
        $this->assertEquals(99, $storage->requestBalance('web'));

        $response->method('hasError')->willReturn(true);
        $this->expectException(\Throwable::class);
        $storage->requestHistory(1, 0, 10);
        $this->assertEquals(0, $storage->requestBalance('web'));

        $rpc->method('send')->willThrowException(new \Exception());
        $this->assertEquals([], $storage->requestHistory(1, 0, 10));
        $this->assertEquals(0, $storage->requestBalance('web'));
    }
}
