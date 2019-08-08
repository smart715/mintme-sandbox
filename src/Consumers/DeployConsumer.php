<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\Model\DeployCallbackMessage;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class DeployConsumer implements ConsumerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var EntityManagerInterface */
    private $em;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        LoggerInterface $logger,
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $em,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->logger = $logger;
        $this->tokenManager = $tokenManager;
        $this->em = $em;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        $this->logger->info('[deploy-consumer] Received new message: '.json_encode($msg->body));

        /** @var string|null $body */
        $body = $msg->body;

        try {
            $clbResult = DeployCallbackMessage::parse(
                json_decode((string)$body, true)
            );

        } catch (\Throwable $exception) {
            $this->logger->warning(
                '[deploy-consumer] Failed to parse incoming message',
                [$msg->body]
            );

            return true;
        }

        try {
            $token = $this->tokenManager->findByName($clbResult->getTokenName());

            if (!$token) {
                $this->logger->info('[deposit-consumer] Invalid token "'.$clbResult->getTokenName().'" given');

                return true;
            }

            if (!$clbResult->getAddress()) {
                if ($token->getDeployCost()) {
                    $this->balanceHandler->deposit(
                        $token->getProfile()->getUser(),
                        Token::getFromSymbol(Token::WEB_SYMBOL),
                        $this->moneyWrapper->parse($token->getDeployCost(), Token::WEB_SYMBOL)
                    );
                    $token->setDeployCost('');
                }
            }

            $token->setAddress($clbResult->getAddress());

            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error(
                '[deposit-consumer] Failed to update token address. Retry operation. Reason:'. $exception->getMessage()
            );

            return false;
        }

        return true;
    }
}
