<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Deposit\Model\DepositCallbackMessage;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManagerInterface;
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
    public function execute(AMQPMessage $msg)
    {
        $clbResult = DepositCallbackMessage::parse(
            json_decode((string)$msg->body, true)
        );

        /** @var User $user */
        $user = $this->userManager->find($clbResult->getUserId());

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
                )
            );

            $this->logger->info('[deposit-consumer] Deposit ('.json_encode($clbResult->toArray()).') returned back');
        } catch (\Throwable $exception) {
            $this->logger->error('[deposit-consumer] Failed to update balance. Retry operation');
            sleep(10);

            return false;
        }

        return true;
    }
}
