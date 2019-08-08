<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\ClockInterface;
use App\Wallet\Money\MoneyWrapper;
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

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var ClockInterface */
    private $clock;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        UserManagerInterface $userManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper,
        ClockInterface $clock
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->userManager = $userManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
        $this->clock = $clock;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg): bool
    {
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

        if ('fail' === $clbResult->getStatus()) {
            try {
                $tradable = $this->cryptoManager->findBySymbol($clbResult->getCrypto())
                    ?? $this->tokenManager->findByName($clbResult->getCrypto());

                if (!$tradable) {
                    $this->logger->info('[payment-consumer] Invalid crypto "'.$clbResult->getCrypto().'" given');

                    return true;
                }

                if ($tradable instanceof Token
                    && null != ($crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL))) {
                    $this->balanceHandler->deposit(
                        $user,
                        Token::getFromCrypto($crypto),
                        $crypto->getFee()
                    );
                }

                $this->balanceHandler->deposit(
                    $user,
                    $tradable instanceof Token ? $tradable: Token::getFromCrypto($tradable),
                    $tradable instanceof Token
                        ? new Money($clbResult->getAmount(), new Currency(MoneyWrapper::TOK_SYMBOL))
                        : $this->moneyWrapper->parse($clbResult->getAmount(), $tradable->getSymbol())
                            ->add($tradable->getFee())
                );

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
        }

        return true;
    }
}
