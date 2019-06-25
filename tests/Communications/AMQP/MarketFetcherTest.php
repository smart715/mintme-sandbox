<?php declare(strict_types = 1);

namespace App\Tests\Communications\AMQP;

use App\Communications\AMQP\MarketProducer;
use App\Exchange\Config\Config;
use App\Exchange\Market;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PHPUnit\Framework\TestCase;

class MarketFetcherTest extends TestCase
{
    public function testSend(): void
    {
        $market = $this->createMock(Market::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('getOffset')->willReturn(0);

        $producer = $this->createMock(ProducerInterface::class);
        $producer->expects($this->once())->method('publish');

        $mp = new MarketProducer($producer, $config);
        $mp->send($market);
    }

    public function testSendWithOffset(): void
    {
        $market = $this->createMock(Market::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('getOffset')->willReturn(1);

        $producer = $this->createMock(ProducerInterface::class);
        $producer->expects($this->never())->method('publish');

        $mp = new MarketProducer($producer, $config);
        $mp->send($market);
    }
}
