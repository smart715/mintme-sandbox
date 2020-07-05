<?php declare(strict_types = 1);

namespace App\Producer;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/** @codeCoverageIgnore */
class MarketDelayedProducer extends Producer
{
    private const MARKET_DELAYED_QUQUE_NAME = 'market-delayed';
    private const MARKET_EXCHANGE_NAME = 'market';
    private const MARKET_QUQUE_NAME = 'market';

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

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }
        // declare market queue and bind to exchange
        $this->getChannel()->queue_declare(
            self::MARKET_QUQUE_NAME,
            self::QUEUE_IS_PASSIVE,
            self::QUEUE_IS_DURABLE,
            self::QUEUE_IS_EXCLUSIVE,
            self::QUEUE_IS_AUTO_DELETE,
            self::QUEUE_IS_NOWAIT,
        );
        $this->getChannel()->queue_bind(self::MARKET_QUQUE_NAME, self::MARKET_EXCHANGE_NAME);

        // declare market-delayed queue and bind to market-delayed exchange
        $this->getChannel()->queue_declare(
            self::MARKET_DELAYED_QUQUE_NAME,
            self::QUEUE_IS_PASSIVE,
            self::QUEUE_IS_DURABLE,
            self::QUEUE_IS_EXCLUSIVE,
            self::QUEUE_IS_AUTO_DELETE,
            self::QUEUE_IS_NOWAIT,
            [
			    'x-message-ttl' => ['I', 15000],
                'x-dead-letter-exchange' => [
                    'S', self::MARKET_EXCHANGE_NAME, // sends message from market delayed queue to market queise after 15s
                ],

            ]
        );

        $this->getChannel()->queue_bind(self::MARKET_DELAYED_QUQUE_NAME, $this.exchangeOptions['name']);

        $this->getChannel()->basic_publish($msg, $this.exchangeOptions['name'], $routingKey);

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
