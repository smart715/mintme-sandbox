<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\ClockInterface;
use App\Wallet\Deposit\Model\DepositCallbackMessage;
use App\Wallet\Money\MoneyWrapperInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

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

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var ClockInterface */
    private $clock;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        UserManagerInterface $userManager,
        CryptoManagerInterface $cryptoManager,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper,
        ClockInterface $clock
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->userManager = $userManager;
        $this->cryptoManager = $cryptoManager;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
        $this->clock = $clock;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
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
            $crypto = $this->cryptoManager->findBySymbol($clbResult->getCrypto());

            if (!$crypto) {
                $this->logger->info('[deposit-consumer] Invalid crypto "'.$clbResult->getCrypto().'" given');

                return true;
            }

            $this->balanceHandler->deposit(
                $user,
                Token::getFromCrypto($crypto),
                $this->moneyWrapper->parse(
                    $clbResult->getAmount(),
                    $crypto->getSymbol()
                )
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
