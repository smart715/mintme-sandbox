<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Consumers\MarketConsumer;
use App\Exchange\Market;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MarketConsumerTest extends TestCase
{
    public function testExecute(): void
    {
        $mc = new MarketConsumer(
            $this->mockLogger(),
            $this->mockStatusManager($this->once()),
            $this->mockMarketNameConverter(),
            $this->mockMarketProducer($this->never()),
            $this->mockEM()
        );

        $this->assertTrue(
            $mc->execute(
                $this->mockMessage(serialize($this->createMock(Market::class)))
            )
        );
    }

    public function testExecuteWithoutMarket(): void
    {
        $mc = new MarketConsumer(
            $this->mockLogger(),
            $this->mockStatusManager($this->never()),
            $this->mockMarketNameConverter(),
            $this->mockMarketProducer($this->never()),
            $this->mockEM()
        );

        $this->assertTrue(
            $mc->execute(
                $this->mockMessage("O:1:\"a\":1:{s:5:\"value\";s:3:\"100\";}")
            )
        );
    }

    public function testExecuteWithoutSerializedObject(): void
    {
        $mc = new MarketConsumer(
            $this->mockLogger(),
            $this->mockStatusManager($this->never()),
            $this->mockMarketNameConverter(),
            $this->mockMarketProducer($this->never()),
            $this->mockEM()
        );

        $this->assertTrue(
            $mc->execute(
                $this->mockMessage("foo")
            )
        );
    }

    public function testExecuteWithException(): void
    {
        $sm = $this->createMock(MarketStatusManagerInterface::class);
        $sm->expects($this->once())->method('updateMarketStatus')->willThrowException(new Exception());

        $mc = new MarketConsumer(
            $this->mockLogger(),
            $sm,
            $this->mockMarketNameConverter(),
            $this->mockMarketProducer($this->once()),
            $this->mockEM()
        );

        $this->assertTrue(
            $mc->execute(
                $this->mockMessage(serialize($this->createMock(Market::class)))
            )
        );
    }

    private function mockMessage(string $message): AMQPMessage
    {
        $msg = $this->createMock(AMQPMessage::class);
        $msg->body = $message;

        return $msg;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function mockMarketNameConverter(): MarketNameConverterInterface
    {
        return $this->createMock(MarketNameConverterInterface::class);
    }

    /** @return MarketStatusManagerInterface|MockObject */
    private function mockStatusManager(Invocation $invocation): MarketStatusManagerInterface
    {
        $sm = $this->createMock(MarketStatusManagerInterface::class);
        $sm->expects($invocation)->method('updateMarketStatus');

        return $sm;
    }

    private function mockMarketProducer(Invocation $invocation): MarketAMQPInterface
    {
        $producer = $this->createMock(MarketAMQPInterface::class);
        $producer->expects($invocation)->method('send');

        return $producer;
    }

    private function mockEM(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn(
            $this->createMock(Connection::class)
        );

        return $em;
    }
}
