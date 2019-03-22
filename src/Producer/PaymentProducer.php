<?php declare(strict_types = 1);

namespace App\Producer;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class PaymentProducer extends Producer
{
    private const QUEUE_NAME = 'payment';

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

        $this->declareQueue($this->getChannel(), self::QUEUE_NAME);
        $this->getChannel()->basic_publish($msg, '', self::QUEUE_NAME);

        $this->logger->debug('[Withdraw] Payment message was published', [
            'amqp' => [
                'body' => $msgBody,
                'routingkeys' => self::QUEUE_NAME,
                'properties' => $additionalProperties,
                'headers' => $headers,
            ],
        ]);
    }

    private function declareQueue(AMQPChannel $channel, string $QUEUE_NAME): array
    {
        return $channel->queue_declare(
            $QUEUE_NAME,
            self::QUEUE_IS_PASSIVE,
            self::QUEUE_IS_DURABLE,
            self::QUEUE_IS_EXCLUSIVE,
            self::QUEUE_IS_AUTO_DELETE,
            self::QUEUE_IS_NOWAIT
        );
    }
}
