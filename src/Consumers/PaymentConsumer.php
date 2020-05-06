<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\WithdrawCompletedEvent;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\BalanceContext;
use App\Exchange\Balance\Strategy\PaymentCryptoStrategy;
use App\Exchange\Balance\Strategy\PaymentTokenStrategy;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\ClockInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentConsumer implements ConsumerInterface
{
    private const STATUS_OK = 'ok';

    private const STATUS_FAIL = 'fail';

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
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg): bool
    {
        $this->em->clear();

        DBConnection::reconnectIfDisconnected($this->em);

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
            $this->logger->info('[payment-consumer] Invalid crypto "'.$clbResult->getCrypto().'" given');

            return true;
        }

        if (self::STATUS_FAIL === $clbResult->getStatus()) {
            try {
                $strategy = $tradable instanceof Token
                    ? new PaymentTokenStrategy(
                        $this->balanceHandler,
                        $this->cryptoManager,
                        $this->moneyWrapper
                    )
                    : new PaymentCryptoStrategy($this->balanceHandler, $this->moneyWrapper);

                $balanceContext = new BalanceContext($strategy);
                $balanceContext->doDeposit($tradable, $user, $clbResult->getAmount());

                $this->logger->info(
                    '[payment-consumer] Payment ('.json_encode($clbResult->toArray()).') returned back'
                );
            } catch (\Throwable $exception) {
                $this->logger->error(
                    '[payment-consumer] Failed to resume payment. Retry operation. Reason:'. $exception->getMessage()
                );
                $this->clock->sleep(10);

                return false;
            }
        } elseif (self::STATUS_OK === $clbResult->getStatus()) {
            $this->eventDispatcher->dispatch(
                WithdrawCompletedEvent::NAME,
                new WithdrawCompletedEvent($tradable, $user, $clbResult->getAmount())
            );
        }

        return true;
    }
}
