<?php declare(strict_types = 1);

namespace App\Producer;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/** @codeCoverageIgnore */
class MarketDelayedProducer extends Producer
{
    private const MARKET_DELAYED_QUQUE_NAME = 'market';
    private const MARKET_DELAYED_EXCHANGE_NAME = 'market';
    private const DELAY = 15000;

    private const QUEUE_IS_PASSIVE      = false;
    private const QUEUE_IS_DURABLE      = true;
    private const QUEUE_IS_EXCLUSIVE    = false;
    private const QUEUE_IS_AUTO_DELETE  = false;
    private const QUEUE_IS_NOWAIT       = false;

    /** @inheritDoc */
    public function publish($msgBody, $routingKey = '', $additionalProperties = [], ?array $headers = null): void
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage($msgBody, array_merge($this->getBasicProperties(), $additionalProperties));
        $headersTable = new AMQPTable(['x-delay' => self::DELAY]);
        $msg->set('application_headers', $headersTable);

        $this->getChannel()->queue_declare(
            self::MARKET_DELAYED_QUQUE_NAME,
            self::QUEUE_IS_PASSIVE,
            self::QUEUE_IS_DURABLE,
            self::QUEUE_IS_EXCLUSIVE,
            self::QUEUE_IS_AUTO_DELETE,
            self::QUEUE_IS_NOWAIT,
        );

        $this->getChannel()->exchange_declare(
            self::MARKET_DELAYED_EXCHANGE_NAME,
            'x-delayed-message',
            false,
            true,
            false,
            false,
            false,
            ['x-delayed-type' => ['s', 'fanout']],
        );

        $this->getChannel()->queue_bind(self::MARKET_DELAYED_QUQUE_NAME, self::MARKET_DELAYED_EXCHANGE_NAME);
        $this->getChannel()->basic_publish($msg, self::MARKET_DELAYED_EXCHANGE_NAME, $routingKey);

        $this->logger->debug('[Market] Delayed message published', [
            'amqp' => [
                'body' => $msgBody,
                'routingkeys' => $routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers,
            ],
        ]);
    }
}
