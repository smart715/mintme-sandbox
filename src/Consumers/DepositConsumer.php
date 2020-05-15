<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\DepositCompletedEvent;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\BalanceContext;
use App\Exchange\Balance\Strategy\DepositCryptoStrategy;
use App\Exchange\Balance\Strategy\DepositTokenStrategy;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\ClockInterface;
use App\Wallet\Deposit\Model\DepositCallbackMessage;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DepositConsumer implements ConsumerInterface
{
    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var LoggerInterface */
    private $logger;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var ClockInterface */
    private $clock;

    /** @var WalletInterface */
    private $depositCommunicator;

    /** @var EntityManagerInterface */
    private $em;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        UserManagerInterface $userManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper,
        ClockInterface $clock,
        WalletInterface $depositCommunicator,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->userManager = $userManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
        $this->clock = $clock;
        $this->depositCommunicator = $depositCommunicator;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        $this->em->clear();

        DBConnection::reconnectIfDisconnected($this->em);

        $this->logger->info('[deposit-consumer] Received new message: '.json_encode($msg->body));

        /** @var string|null $body */
        $body = $msg->body;

        try {
            $clbResult = DepositCallbackMessage::parse(
                json_decode((string)$body, true)
            );
        } catch (\Throwable $exception) {
            $this->logger->warning(
                '[deposit-consumer] Failed to parse incoming message',
                [$msg->body]
            );

            return true;
        }

        /** @var User|null $user */
        $user = $this->userManager->find($clbResult->getUserId());

        if (!$user) {
            $this->logger->warning(
                '[deposit-consumer] Received new message with undefined user',
                $clbResult->toArray()
            );

            return true;
        }

        try {
            $tradable =$this->cryptoManager->findBySymbol($clbResult->getCrypto())
                ?? $this->tokenManager->findByName($clbResult->getCrypto());

            if (!$tradable) {
                $this->logger->info('[deposit-consumer] Invalid crypto "'.$clbResult->getCrypto().'" given');

                return true;
            }

            $strategy = $tradable instanceof Token
                ? new DepositTokenStrategy(
                    $this->balanceHandler,
                    $this->depositCommunicator,
                    $this->em,
                    $this->moneyWrapper
                )
                : new DepositCryptoStrategy($this->balanceHandler, $this->moneyWrapper);

            $balanceContext = new BalanceContext($strategy);
            $balanceContext->doDeposit($tradable, $user, $clbResult->getAmount());

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new DepositCompletedEvent($tradable, $user, $clbResult->getAmount()),
                DepositCompletedEvent::NAME
            );

            $this->logger->info('[deposit-consumer] Deposit ('.json_encode($clbResult->toArray()).') paid');
        } catch (\Throwable $exception) {
            $this->logger->error(
                '[deposit-consumer] Failed to update balance. Retry operation. Reason:'. $exception->getMessage()
            );
            $this->clock->sleep(10);

            return false;
        }

        return true;
    }
}
