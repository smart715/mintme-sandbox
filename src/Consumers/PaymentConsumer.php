<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use Money\Currency;
use Money\Money;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class PaymentConsumer implements ConsumerInterface
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

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        UserManagerInterface $userManager,
        CryptoManagerInterface $cryptoManager,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->userManager = $userManager;
        $this->cryptoManager = $cryptoManager;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg): bool
    {
        $clbResult = WithdrawCallbackMessage::parse(
            json_decode($msg->body, true)
        );

        /** @var User $user */
        $user = $this->userManager->find($clbResult->getUserId());

        if ('fail' === $clbResult->getStatus()) {
            try {
                $this->logger->info('[payment-consumer] Received new message: '.json_encode($clbResult->toArray()));

                $crypto = $this->cryptoManager->findBySymbol($clbResult->getCrypto());

                if (!$crypto) {
                    $this->logger->info('[payment-consumer] Invalid crypto "'.$clbResult->getCrypto().'" given');

                    return true;
                }

                $this->balanceHandler->deposit(
                    $user,
                    Token::getFromCrypto($crypto),
                    $this->moneyWrapper->parse(
                        $clbResult->getAmount(),
                        $crypto->getSymbol()
                    )->add($crypto->getFee())
                );
                $this->logger->info('[payment-consumer] Payment ('.json_encode($clbResult->toArray()).') returned back');
            } catch (\Throwable $exception) {
                $this->logger->error('[payment-consumer] Failed to resume payment. Retry operation');

                return false;
            }
        }

        return true;
    }
}
