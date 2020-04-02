<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Consumers\Helpers\DBConnection;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\SmartContract\Model\DeployCallbackMessage;
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

    /** @var int */
    private $coinbaseApiTimeout;

    /** @var EntityManagerInterface */
    private $em;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    public function __construct(
        LoggerInterface $logger,
        int $coinbaseApiTimeout,
        EntityManagerInterface $em,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->logger = $logger;
        $this->coinbaseApiTimeout = $coinbaseApiTimeout;
        $this->em = $em;
        $this->balanceHandler = $balanceHandler;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        DBConnection::reconnectIfDisconnected($this->em);

        /** @var string $body */
        $body = $msg->body ?? '';

        $this->logger->info("[deploy-consumer] Received new message: {$body}");

        try {
            $clbResult = DeployCallbackMessage::parse(json_decode($body, true));
        } catch (\Throwable $exception) {
            $this->logger->warning("[deploy-consumer] Failed to parse incoming message", [$msg->body]);

            return true;
        }

        try {
            // wait to make sure that the the payment of the cost is done
            sleep($this->coinbaseApiTimeout + 10);
            $this->em->clear();
            $repo = $this->em->getRepository(Token::class);
            /** @var Token|null $token */
            $token = $repo->findOneBy(['name' => $clbResult->getTokenName()]);

            if (!$token) {
                $this->logger->info("[deploy-consumer] Invalid token '{$clbResult->getTokenName()}' given");

                return true;
            }

            /** @var LockIn */
            $lockIn = $token->getLockIn();

            if (!$clbResult->getAddress()) {
                if ($token->getDeployCost()) {
                    $amount = new Money((string)$token->getDeployCost(), new Currency(Token::WEB_SYMBOL));

                    $this->balanceHandler->deposit(
                        $token->getProfile()->getUser(),
                        Token::getFromSymbol(Token::WEB_SYMBOL),
                        $amount
                    );

                    $token->setDeployCost('');
                    $token->setDeployed(null);

                    $this->logger->info(
                        '[deploy-consumer] the money is payed back returned back'
                        . json_encode([
                            'userId' => $token->getProfile()->getUser()->getId(),
                            'tokenName' => $token->getName(),
                            'amount' => $amount,
                        ])
                    );
                }
            } else {
                $lockIn->setReleasedAtStart($lockIn->getReleasedAmount()->getAmount());
                $lockIn->setAmountToRelease($lockIn->getFrozenAmount());
                $token->setDeployed(new \DateTimeImmutable());
                $token->setAddress($clbResult->getAddress());
            }

            $this->em->persist($lockIn);
            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error(
                '[deploy-consumer] Failed to update token address. Retry operation.'
                . json_encode([
                    'Reason' => $exception->getMessage(),
                ])
            );

            return false;
        }

        return true;
    }
}
