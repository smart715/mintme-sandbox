<?php

namespace App\Withdraw\Communicator;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use Money\Money;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPCommunicator implements CommunicatorInterface
{
    private const RETRY_DELAY = 30000;

    /** @var ProducerInterface */
    private $paymentProducer;

    /** @var ProducerInterface */
    private $paymentRetryProducer;

    /** @var string */
    private $service;

    /** @var float */
    private $fee;

    public function __construct(
        ProducerInterface $paymentProducer,
        ProducerInterface $paymentRetryProducer,
        string $service,
        float $fee
    ) {
        $this->paymentProducer = $paymentProducer;
        $this->paymentRetryProducer = $paymentRetryProducer;
        $this->service = $service;
        $this->fee = $fee;
    }

    public function sendWithdrawRequest(User $user, Money $balance, string $address, Crypto $crypto): void
    {
        $this->paymentProducer->publish(
            $this->createPayload($user->getId(), $balance->getAmount(), $address, $crypto->getSymbol()),
            '',
            $this->createMessageOptions()
        );
    }

    public function sendRetryMessage(WithdrawCallbackMessage $message): void
    {
        $this->paymentRetryProducer->publish(
            json_encode($message->toArray()) ?: '',
            '',
            array_merge($this->createMessageOptions(), [
                'expiration' => self::RETRY_DELAY,
            ])
        );
    }

    private function createPayload(int $id, string $amount, string $recipient, string $crypto): string
    {
        return json_encode([
            'id' => $id,
            'amount' => $amount,
            'recipient' => $recipient,
            'crypto' => $crypto,
            'service' => $this->service,
            'fee' => $this->fee,
        ]) ?: '';
    }

    private function createMessageOptions(): array
    {
        return [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];
    }
}
