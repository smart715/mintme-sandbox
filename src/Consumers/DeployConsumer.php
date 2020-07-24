<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Communications\DeployCostFetcherInterface;
use App\Consumers\Helpers\DBConnection;
use App\Entity\DeployTokenReward;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\User;
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

    /** @var DeployCostFetcherInterface */
    private $deployCostFetcher;

    public function __construct(
        LoggerInterface $logger,
        int $coinbaseApiTimeout,
        EntityManagerInterface $em,
        BalanceHandlerInterface $balanceHandler,
        DeployCostFetcherInterface $deployCostFetcher
    ) {
        $this->logger = $logger;
        $this->coinbaseApiTimeout = $coinbaseApiTimeout;
        $this->em = $em;
        $this->balanceHandler = $balanceHandler;
        $this->deployCostFetcher = $deployCostFetcher;
    }

    /** {@inheritdoc} */
    public function execute(AMQPMessage $msg)
    {
        if (!DBConnection::initConsumerEm(
            'deploy-consumer',
            $this->em,
            $this->logger
        )) {
            return false;
        }

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
            /** @var User */
            $user = $token->getProfile()->getUser();

            if (!$clbResult->getAddress()) {
                if (null !== $token->getDeployCost()) {
                    $amount = new Money($token->getDeployCost(), new Currency(Token::WEB_SYMBOL));

                    $this->balanceHandler->deposit(
                        $user,
                        Token::getFromSymbol(Token::WEB_SYMBOL),
                        $amount
                    );

                    $token->setAddress('');
                    $token->setDeployCost(null);
                    $token->setDeployed(null);

                    $this->logger->info(
                        '[deploy-consumer] the money is payed back returned back'
                        . json_encode([
                            'userId' => $user->getId(),
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

                $this->setDeployCostReward($user);
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

    private function setDeployCostReward(User $user): void
    {
        $referencer = $user->getReferencer();

        if ($referencer) {
            $reward = $this->deployCostFetcher->getDeployCostReferralReward();

            if ($reward->isPositive()) {
                $userDeployTokenReward = new DeployTokenReward($user, $reward);
                $referencerDeployTokenReward = new DeployTokenReward($referencer, $reward);

                $this->balanceHandler->deposit(
                    $user,
                    Token::getFromSymbol(Token::WEB_SYMBOL),
                    $reward
                );

                $this->balanceHandler->deposit(
                    $referencer,
                    Token::getFromSymbol(Token::WEB_SYMBOL),
                    $reward
                );

                $this->em->persist($userDeployTokenReward);
                $this->em->persist($referencerDeployTokenReward);
            }
        }
    }
}
