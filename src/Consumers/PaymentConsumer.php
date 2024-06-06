<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\User;
use App\Events\WithdrawCompletedEvent;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Wallet\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentConsumer implements ConsumerInterface
{
    private const STATUS_OK = 'ok';

    private LoggerInterface $logger;

    private UserManagerInterface $userManager;

    private CryptoManagerInterface $cryptoManager;

    private TokenManagerInterface $tokenManager;

    private EntityManagerInterface $em;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        UserManagerInterface $userManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userManager = $userManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg): bool
    {
        if (!DBConnection::initConsumerEm(
            'payment-consumer',
            $this->em,
            $this->logger
        )) {
            return false;
        }

        $this->em->clear();

        $this->logger->info('[payment-consumer] Received new message: '.json_encode($msg->body));

        try {
            $clbResult = WithdrawCallbackMessage::parse(
                json_decode($msg->body, true)
            );
        } catch (\Throwable $exception) {
            $this->logger->warning(
                '[payment-consumer] Failed to parse incoming message',
                [$msg->body]
            );

            return true;
        }

        /** @var ?User $user */
        $user = $this->userManager->find($clbResult->getUserId());

        if (!$user) {
            $this->logger->warning('[payment-consumer] User not found', $clbResult->toArray());

            return true;
        }

        $tradable = $this->cryptoManager->findBySymbol($clbResult->getCrypto())
            ?? $this->tokenManager->findByName($clbResult->getCrypto());

        if (!$tradable) {
            $this->logger->warning('[payment-consumer] Invalid crypto "'.$clbResult->getCrypto().'" given');

            return true;
        }

        if (self::STATUS_OK === $clbResult->getStatus()) {
            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new WithdrawCompletedEvent(
                    $tradable,
                    $user,
                    $clbResult->getAmount(),
                    $clbResult->getAddress(),
                    $this->cryptoManager->getNetworkName($clbResult->getCryptoNetwork()),
                ),
                WithdrawCompletedEvent::NAME
            );
        }

        return true;
    }
}
