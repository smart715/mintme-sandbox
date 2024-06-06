<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\RabbitMQCommunicator;
use App\Communications\RestRpcInterface;
use PHPUnit\Framework\TestCase;

class RabbitMQCommunicatorTest extends TestCase
{
    public function testFetchConsumers(): void
    {
        $responseData = [
            ['queue' => ['name' => 'test']],
            ['queue' => ['name' => 'test2']],
        ];
        $communicator = new RabbitMQCommunicator(
            $this->mockRestRpc($responseData)
        );

        $this->assertEquals(
            ['test' => 'test', 'test2' => 'test2'],
            $communicator->fetchConsumers()
        );
    }

    private function mockRestRpc(array $responseData = []): RestRpcInterface
    {
        $restRpc = $this->createMock(RestRpcInterface::class);
        $restRpc->expects($this->once())
            ->method('send')
            ->willReturn(json_encode($responseData));

        return $restRpc;
    }
}
