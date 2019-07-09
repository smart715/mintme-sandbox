<?php declare(strict_types = 1);

namespace App\Producer;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/** @codeCoverageIgnore */
class RetryPaymentProducer extends Producer
{
    private const CALLBACK_EXCHANGE_NAME        = 'payment-callback-exchange';
    private const RETRY_CALLBACK_QUEUE_NAME     = 'payment-callback-retry';
    private const RETRY_CALLBACK_EXCHANGE_NAME  = 'payment-callback-retry-exchange';

    private const QUEUE_IS_PASSIVE      = false;
    private const QUEUE_IS_DURABLE      = false;
    private const QUEUE_IS_EXCLUSIVE    = false;
    private const QUEUE_IS_AUTO_DELETE  = false;
    private const QUEUE_IS_NOWAIT       = false;

    /** {@inheritdoc} */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), ?array $headers = null): void
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage($msgBody, array_merge($this->getBasicProperties(), $additionalProperties));

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        $this->declareQueue($this->getChannel(), self::RETRY_CALLBACK_QUEUE_NAME);
        $this->getChannel()->basic_publish($msg, '', self::RETRY_CALLBACK_QUEUE_NAME);

        $this->logger->debug('[Withdraw] Retry payment message was published', [
            'amqp' => [
                'body' => $msgBody,
                'routingkeys' => self::RETRY_CALLBACK_QUEUE_NAME,
                'properties' => $additionalProperties,
                'headers' => $headers,
            ],
        ]);
    }

    private function declareQueue(AMQPChannel $channel, string $queueName): void
    {
        $channel->queue_declare(
            $queueName,
            self::QUEUE_IS_PASSIVE,
            self::QUEUE_IS_DURABLE,
            self::QUEUE_IS_EXCLUSIVE,
            self::QUEUE_IS_AUTO_DELETE,
            self::QUEUE_IS_NOWAIT,
            [
                'x-dead-letter-exchange' => [
                    'S', self::CALLBACK_EXCHANGE_NAME,
                ],
            ]
        );

        $channel->exchange_declare(self::RETRY_CALLBACK_EXCHANGE_NAME, 'fanout');
        $channel->queue_bind(self::RETRY_CALLBACK_QUEUE_NAME, self::RETRY_CALLBACK_EXCHANGE_NAME);
    }
}
