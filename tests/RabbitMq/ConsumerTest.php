<?php declare(strict_types = 1);

namespace App\Tests\RabbitMq;

use App\RabbitMq\Consumer;
use PhpAmqpLib\Connection\AbstractConnection;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
{
    public function testReconnectWhenAlreadyConnected(): void
    {
        $consumer = new Consumer($this->mockConnection(true));

        $consumer->reconnect();
    }

    public function testReconnectWhenNotConnected(): void
    {
        $consumer = new Consumer($this->mockConnection(false));

        $consumer->reconnect();
    }

    private function mockConnection(bool $isConnected): AbstractConnection
    {
        $conn = $this->createMock(AbstractConnection::class);
        $conn->expects($this->exactly(2))->method('isConnected')->willReturn($isConnected);
        $conn->expects(!$isConnected ? $this->once() : $this->never())->method('reconnect');

        return $conn;
    }
}
