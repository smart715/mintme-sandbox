<?php declare(strict_types = 1);

namespace App\Communications\AMQP;

use App\Entity\User;
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

    public function send(Market $market, ?User $user = null, int $retried = 0): void
    {
        // TODO: split consumers for all branches.
        if ($this->config->getOffset() > 0 && !$this->config->isMarketConsumerEnabled()) {
            return;
        }

        $this->producer->publish((string)json_encode([
            'retried' => $retried,
            'base' => $market->getBase()->getSymbol(),
            'quote' => $market->getQuote()->getSymbol(),
            'user_id' => $user ? $user->getId() : null,
        ]));
    }
}
