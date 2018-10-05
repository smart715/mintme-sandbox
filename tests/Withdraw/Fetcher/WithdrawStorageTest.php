<?php

namespace App\Tests\Withdraw\Fetcher;

use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Withdraw\Fetcher\Storage\WithdrawStorage;
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
        $response->method('getResult')->willReturn(['foo']);

        $rpc->method('send')->willReturn($response);

        $storage = new WithdrawStorage($rpc, 'mintme');

        $this->assertEquals(['foo'], $storage->requestHistory(1));
        $this->assertEquals(['foo'], $storage->requestBalance('web'));

        $response->method('hasError')->willReturn(true);
        $this->assertEquals([], $storage->requestHistory(1));
        $this->assertEquals([], $storage->requestBalance('web'));

        $rpc->method('send')->willThrowException(new \Exception());
        $this->assertEquals([], $storage->requestHistory(1));
        $this->assertEquals([], $storage->requestBalance('web'));
    }
}
