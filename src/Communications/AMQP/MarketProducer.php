<?php declare(strict_types = 1);

namespace App\Communications\AMQP;

use App\Exchange\Config\Config;
use App\Exchange\Market;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class MarketProducer implements MarketAMQPInterface
{
    /** @var ProducerInterface */
    private $producer;

    /** @var Config */
    private $config;

    public function __construct(ProducerInterface $producer, Config $config)
    {
        $this->producer = $producer;
        $this->config = $config;
    }

    public function send(Market $market): void
    {
        // TODO: split consumers for all branches.
        if ($this->config->getOffset() > 0) {
            return;
        }

        $this->producer->publish(serialize($market));
    }
}
