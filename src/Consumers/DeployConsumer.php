<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Communications\DeployCostFetcherInterface;
use App\Consumers\Helpers\DBConnection;
use App\Entity\DeployTokenReward;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TokenCrypto;
use App\Entity\User;
use App\Events\ConnectCompletedEvent;
use App\Events\DeployCompletedEvent;
use App\Events\TokenEvents;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\SmartContract\Model\DeployCallbackMessage;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeployConsumer implements ConsumerInterface
{
    private LoggerInterface $logger;
    private int $coinbaseApiTimeout;
    private EntityManagerInterface $em;
    private BalanceHandlerInterface $balanceHandler;
    private DeployCostFetcherInterface $deployCostFetcher;
    private EventDispatcherInterface $eventDispatcher;
    private CryptoManagerInterface $cryptoManager;
    private MoneyWrapperInterface $moneyWrapper;
    private MarketFactoryInterface $marketFactory;
    private MarketStatusManagerInterface $marketStatusManager;

    public function __construct(
        LoggerInterface $logger,
        int $coinbaseApiTimeout,
        EntityManagerInterface $em,
        BalanceHandlerInterface $balanceHandler,
        DeployCostFetcherInterface $deployCostFetcher,
        EventDispatcherInterface $eventDispatcher,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper,
        MarketFactoryInterface $marketFactory,
        MarketStatusManagerInterface $marketStatusManager
    ) {
        $this->logger = $logger;
        $this->coinbaseApiTimeout = $coinbaseApiTimeout;
        $this->em = $em;
        $this->balanceHandler = $balanceHandler;
        $this->deployCostFetcher = $deployCostFetcher;
        $this->eventDispatcher = $eventDispatcher;
        $this->cryptoManager = $cryptoManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketFactory = $marketFactory;
        $this->marketStatusManager = $marketStatusManager;
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
        $body = $msg->body;

        $this->logger->info("[deploy-consumer] Received new message: {$body}");

        try {
            $clbResult = DeployCallbackMessage::parse(json_decode($body, true));
        } catch (\Throwable $exception) {
            $this->logger->warning("[deploy-consumer] Failed to parse incoming message", [$msg->body]);

            return true;
        }

        try {
            sleep($this->coinbaseApiTimeout + 10);
            $this->em->clear();
            $repo = $this->em->getRepository(Token::class);
            /** @var Token|null $token */
            $token = $repo->findOneBy(['name' => $clbResult->getTokenName()]);

            if (!$token) {
                $this->logger->warning("[deploy-consumer] Invalid token '{$clbResult->getTokenName()}' given");

                return true;
            }

            $crypto = $this->cryptoManager->findBySymbol($clbResult->getCrypto());
            $deploy = $token->getDeployByCrypto($crypto);

            if (!$deploy) {
                $this->logger->warning(
                    "[deploy-consumer] Invalid token deploy " .
                    "{$clbResult->getTokenName()}/{$clbResult->getCrypto()} given"
                );

                return true;
            }

            $this->processDeploymentMessage($token, $clbResult, $deploy);
        } catch (\Throwable $exception) {
            $this->logger->error(
                '[deploy-consumer] Failed to update token address. Retry operation.'
                . json_encode([
                    'Reason' => $exception->getMessage(),
                ])
            );
            $this->balanceHandler->rollback();

            return false;
        }

        return true;
    }

    private function updateTokenMarkets(Token $token): void
    {
        /** @var array<TokenCrypto> $tokenCryptos */
        $tokenCryptos = $token->getExchangeCryptos()->toArray();

        /** @var TokenCrypto $tokenCrypto */
        foreach ($tokenCryptos as $tokenCrypto) {
            $market = $this->marketFactory->create($tokenCrypto->getCrypto(), $token);
            $this->marketStatusManager->updateMarketStatusNetworks($market);
        }
    }

    private function processDeploymentMessage(Token $token, DeployCallbackMessage $clbResult, TokenDeploy $deploy): void
    {
        $this->balanceHandler->beginTransaction();

        if (DeployCallbackMessage::STATUS_FAILURE === $clbResult->getStatus()) {
            $this->handleDeployFailure($deploy, $token);

            return;
        }

        $this->handleDeploySuccess($token, $deploy, $clbResult);
    }

    private function handleDeploySuccess(Token $token, TokenDeploy $deploy, DeployCallbackMessage $clbResult): void
    {
        $user = $token->getProfile()->getUser();

        $isMainDeploy = $deploy->getId() === $token->getMainDeploy()->getId();

        if ($isMainDeploy) {
            /** @var LockIn $lockIn */
            $lockIn = $token->getLockIn();
            $lockIn->setReleasedAtStart($lockIn->getReleasedAmount()->getAmount());
            $lockIn->setAmountToRelease($lockIn->getFrozenAmount());

            $this->setDeployCostReward($user, $deploy);

            $token->setShowDeployedModal(true);
            $this->em->persist($lockIn);
        }

        $deploy->setDeployDate(new \DateTimeImmutable());
        $deploy->setAddress($clbResult->getAddress());
        $deploy->setTxHash($clbResult->getTxHash());
        $token->setDeployed(true);

        $this->em->persist($deploy);
        $this->em->persist($token);
        $this->em->flush();

        $this->updateTokenMarkets($token);
        $this->dispatchCompletedEvent($token);
    }

    private function dispatchCompletedEvent(Token $token): void
    {
        if ($token->getDeployed()) {
            if ($token->getLastDeploy()->getId() === $token->getMainDeploy()->getId()) {
                $this->eventDispatcher->dispatch(
                    new DeployCompletedEvent($token, $token->getLastDeploy()),
                    TokenEvents::DEPLOYED
                );
            } else {
                $this->eventDispatcher->dispatch(
                    new ConnectCompletedEvent($token, $token->getLastDeploy()),
                    TokenEvents::CONNECTED
                );
            }
        }
    }

    private function setDeployCostReward(User $user, TokenDeploy $deploy): void
    {
        $referencer = $user->getReferencer();

        if ($referencer) {
            $deployCrypto = $deploy->getCrypto();

            $reward = $this->deployCostFetcher->getDeployCostReferralReward($deployCrypto->getMoneySymbol());
            $rewardCrypto = $this->cryptoManager->findBySymbol($reward->getCurrency()->getCode());

            if ($reward->isPositive()) {
                $userDeployTokenReward = new DeployTokenReward($user, $reward);
                $referencerDeployTokenReward = new DeployTokenReward($referencer, $reward);

                $this->balanceHandler->deposit(
                    $user,
                    $rewardCrypto,
                    $reward
                );

                $this->balanceHandler->deposit(
                    $referencer,
                    $rewardCrypto,
                    $reward
                );

                $this->em->persist($userDeployTokenReward);
                $this->em->persist($referencerDeployTokenReward);

                $this->logger->info(
                    '[deploy-consumer] token deploy referral reward'
                    . json_encode([
                        'referredUserId' => $user->getId(),
                        'referrerUserId' => $referencer->getId(),
                        'tokenName' => $deploy->getToken()->getName(),
                        'deployCost' => $this->deployCostFetcher->getCost($deployCrypto->getMoneySymbol())
                            ->getAmount(),
                        'deployCostCurrency' => $deployCrypto->getMoneySymbol(),
                        'rewardAmount' => $reward->getAmount(),
                        'rewardCurrency' => $reward->getCurrency()->getCode(),
                    ])
                );
            }
        }
    }

    private function handleDeployFailure(TokenDeploy $deploy, Token $token): void
    {
        $user = $token->getProfile()->getUser();
        $this->logFailedDeploy($deploy, $user, $token);
        $token->removeDeploy($deploy);
        $this->em->persist($token);
        $this->em->remove($deploy);
        $this->em->flush();
    }

    private function logFailedDeploy(TokenDeploy $deploy, User $user, Token $token): void
    {
        $cost = new Money(
            $deploy->getDeployCost() ?? '0',
            new Currency($deploy->getCrypto()->getMoneySymbol())
        );

        $this->logger->error(
            '[deploy-consumer] deployment failed'
            . json_encode([
                'userId' => $user->getId(),
                'tokenName' => $token->getName(),
                'crypto' => $deploy->getCrypto()->getSymbol(),
                'cost' => $this->moneyWrapper->format($cost, false),
            ])
        );
    }
}
