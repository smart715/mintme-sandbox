<?php declare(strict_types = 1);

namespace App\Communications\AMQP;

use App\Exchange\Market;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class MarketProducer implements MarketAMQPInterface
{
    /** @var ProducerInterface */
    private $producer;

    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    public function send(Market $market): void
    {
        $this->producer->publish(serialize($market));
    }
}
