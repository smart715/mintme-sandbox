<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Events\DepositCompletedEvent;
use App\Events\UserNotificationEvent;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Security;

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

    /** @var Security */
    private $security;

    /** @var ContainerInterface */
    private $container;


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
        EventDispatcherInterface $eventDispatcher,
        ContainerInterface $container,
        Security $security
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
        $this->security = $security;
        $this->container = $container;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        if (!DBConnection::initConsumerEm(
            'deposit-consumer',
            $this->em,
            $this->logger
        )) {
            return false;
        }

        $this->em->clear();

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

        $token = new AnonymousToken('deposit', 'deposit', ['IS_AUTHENTICATED_ANONYMOUSLY']);
        $this->container->get('security.token_storage')->setToken($token);

        if (!$this->security->isGranted('deposit')) {
            $this->logger->info('[deposit-consumer] Deposits are disabled. Canceled.');

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
            $tradable = $this->cryptoManager->findBySymbol($clbResult->getCrypto())
                ?? $this->tokenManager->findByName($clbResult->getCrypto());

            if (!$tradable) {
                $this->logger->info('[deposit-consumer] Invalid crypto "'.$clbResult->getCrypto().'" given');

                return true;
            }

            if ($tradable instanceof Crypto) {
                if (!$this->security->isGranted('not-disabled', $tradable)) {
                    $this->logger->info('[deposit-consumer] Deposit for this crypto was disabled. Cancelled.');

                    return true;
                }
            }

            $strategy = $tradable instanceof Token
                ? new DepositTokenStrategy(
                    $this->balanceHandler,
                    $this->depositCommunicator,
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

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new UserNotificationEvent(
                    $user,
                    UserNotification::DEPOSIT_NOTIFICATION
                ),
                UserNotificationEvent::NAME
            );

            $this->logger->info('[deposit-consumer] Deposit ('.json_encode($clbResult->toArray()).') paid');
        } catch (\Throwable $exception) {
            if ($exception instanceof BalanceException) {
                $this->logger->error(
                    '[deposit-consumer] Failed to update balance. Retry operation. Reason:'. $exception->getMessage()
                );
                $this->clock->sleep(10);

                return false;
            }

            $this->logger->error(
                '[deposit-consumer] Something went wrong during deposit. Reason:'. $exception->getMessage()
            );
        }

        return true;
    }
}
