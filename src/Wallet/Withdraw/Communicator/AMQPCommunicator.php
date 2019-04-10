<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Communicator;

use App\Entity\Crypto;
use App\Entity\User;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\Communicator\Model\WithdrawCallbackMessage;
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

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var string */
    private $service;

    public function __construct(
        ProducerInterface $paymentProducer,
        ProducerInterface $paymentRetryProducer,
        MoneyWrapperInterface $moneyWrapper,
        string $service
    ) {
        $this->paymentProducer = $paymentProducer;
        $this->paymentRetryProducer = $paymentRetryProducer;
        $this->moneyWrapper = $moneyWrapper;
        $this->service = $service;
    }

    public function sendWithdrawRequest(User $user, Money $balance, string $address, Crypto $crypto): void
    {
        $this->paymentProducer->publish(
            $this->createPayload(
                $user->getId(),
                $this->moneyWrapper->format($balance),
                $address,
                $crypto->getSymbol(),
                $this->moneyWrapper->format($crypto->getFee())
            ),
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

    private function createPayload(int $id, string $amount, string $recipient, string $crypto, string $fee): string
    {
        return json_encode([
            'id' => $id,
            'amount' => $amount,
            'recipient' => $recipient,
            'crypto' => $crypto,
            'fee' => $fee,
            'service' => $this->service,
        ]) ?: '';
    }

    private function createMessageOptions(): array
    {
        return [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];
    }
}
