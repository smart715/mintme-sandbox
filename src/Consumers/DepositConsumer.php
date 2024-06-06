<?php declare(strict_types = 1);

namespace App\Consumers;

use App\Communications\Exception\FetchException;
use App\Consumers\Helpers\DBConnection;
use App\Entity\Crypto;
use App\Entity\DepositHash;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Events\DepositCompletedEvent;
use App\Exception\ConsumerException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\DepositContext;
use App\Exchange\Balance\Strategy\DepositCryptoStrategy;
use App\Exchange\Balance\Strategy\DepositTokenStrategy;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Repository\DepositHashRepository;
use App\Security\DisabledServicesVoter;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Deposit\Model\DepositCallbackMessage;
use App\Wallet\Model\BlockchainTransaction;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Security;

class DepositConsumer implements ConsumerInterface
{
    private BalanceHandlerInterface $balanceHandler;
    private LoggerInterface $logger;
    private UserManagerInterface $userManager;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private MoneyWrapperInterface $moneyWrapper;
    private WalletInterface $wallet;
    private EntityManagerInterface $em;
    private EventDispatcherInterface $eventDispatcher;
    private Security $security;
    private ContainerInterface $container;
    private DepositGatewayCommunicator $depositCommunicator;
    private DepositHashRepository $depositHasRepository;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        UserManagerInterface $userManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        LoggerInterface $logger,
        MoneyWrapperInterface $moneyWrapper,
        WalletInterface $wallet,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        ContainerInterface $container,
        Security $security,
        DepositGatewayCommunicator $depositCommunicator,
        DepositHashRepository $depositHasRepository
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->userManager = $userManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
        $this->moneyWrapper = $moneyWrapper;
        $this->wallet = $wallet;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->security = $security;
        $this->container = $container;
        $this->depositCommunicator = $depositCommunicator;
        $this->depositHasRepository = $depositHasRepository;
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

        $this->logger->info('[deposit-consumer] Received new message: ' . json_encode($msg->body));

        /** @var string|null $body */
        $body = $msg->body;

        try {
            $this->balanceHandler->beginTransaction();

            $clbResult = DepositCallbackMessage::parse(
                json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR)
            );

            $this->setSecurityToken();

            $user = $this->userManager->find($clbResult->getUserId());

            if (!$user) {
                throw new ConsumerException('Received new message with undefined user.');
            }

            $tradable = $this->cryptoManager->findBySymbol($clbResult->getAsset()) ??
                $this->tokenManager->findByName($clbResult->getAsset());
            $cryptoNetwork = $this->cryptoManager->findBySymbol($clbResult->getCryptoNetwork());

            $this->assertValidAssets($clbResult, $tradable, $cryptoNetwork);
            $this->assertDepositConfig($tradable);
            $this->assertGatewayDeposit($clbResult);
            $this->assertBlockchainDeposit($tradable, $cryptoNetwork, $clbResult);

            $this->saveDepositHash(
                $clbResult->getHashes(),
                $user,
                $tradable,
                $cryptoNetwork,
            );

            $depositContext = $this->getDepositStrategyContext($tradable, $cryptoNetwork);
            $depositContext->doDeposit($tradable, $user, $clbResult->getAmount());

            $this->depositCommunicator->confirmDeposit(
                $clbResult->getUserId(),
                $clbResult->getHashes(),
                $clbResult->getAsset(),
                $clbResult->getCryptoNetwork(),
            );

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new DepositCompletedEvent(
                    $tradable,
                    $user,
                    $clbResult->getAmount(),
                    $this->cryptoManager->getNetworkName($clbResult->getCryptoNetwork()),
                    $clbResult->getAddress(),
                ),
                DepositCompletedEvent::NAME
            );

            $this->logger->info(
                "[deposit-consumer] Deposit paid",
                $clbResult->toArray(),
            );
        } catch (\Throwable $exception) {
            $loggerMessage = "Something went wrong during deposit";

            if ($exception instanceof \JsonException) {
                $loggerMessage = "Failed to parse incoming message";
            }

            if ($exception instanceof FetchException) {
                $loggerMessage = 'Invalid message received';
            }

            if ($this->balanceHandler->isTransactionStarted()) {
                $this->balanceHandler->rollback();
                $loggerMessage .= " and Transaction was rollback";
            }

            $this->logger->error(
                "[deposit-consumer] " . $loggerMessage .", Error:" . $exception->getMessage(),
                [$body],
            );
        }

        return true;
    }

    private function setSecurityToken(): void
    {
        $securityToken = new AnonymousToken('deposit', 'deposit', ['IS_AUTHENTICATED_ANONYMOUSLY']);
        $this->container->get('security.token_storage')->setToken($securityToken);
    }

    private function assertValidAssets(
        DepositCallbackMessage $clbResult,
        ?TradableInterface $tradable,
        ?Crypto $cryptoNetwork
    ): void {
        if (!$tradable) {
            throw new ConsumerException('Asset not found: ' . $clbResult->getAsset());
        }

        if (!$cryptoNetwork) {
            throw new ConsumerException('Invalid crypto network. ' . $clbResult->getCryptoNetwork());
        }
    }

    private function assertDepositConfig(TradableInterface $tradable): void
    {
        if ($tradable instanceof Token && !$this->security->isGranted(DisabledServicesVoter::TOKEN_DEPOSIT)) {
            throw new ConsumerException('Token deposits are disabled. Canceled.');
        }

        if ($tradable instanceof Crypto) {
            if (!$this->security->isGranted(DisabledServicesVoter::COIN_DEPOSIT)) {
                throw new ConsumerException('Crypto deposits are disabled. Cancelled.');
            }

            if (!$this->security->isGranted('not-disabled', $tradable)) {
                throw new ConsumerException('Deposit for this crypto was disabled. Cancelled.');
            }
        }
    }

    /*
     * Checks against blockchain with external nodes if the info is right
     */
    private function assertBlockchainDeposit(
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        DepositCallbackMessage $clbResult
    ): void {
        /** @var BlockchainTransaction[] $eTransactions */
        $eTransactions = array_map(function (string $hash) use ($tradable, $cryptoNetwork) {
            $depositHash = $this->depositHasRepository->findByHash(
                $hash,
                $tradable instanceof Crypto ? $tradable : $cryptoNetwork,
                $tradable instanceof Token ? $tradable : null,
            );

            if ($depositHash) {
                throw new ConsumerException(
                    'Invalid message received' .
                    "A Deposit hash is duplicated: $hash"
                );
            }

            return $this->depositCommunicator->getBlockchainTransaction(
                $hash,
                $tradable->getSymbol(),
                $cryptoNetwork->getSymbol(),
            );
        }, $clbResult->getHashes());

        $depositAddress = $this->depositCommunicator->getDepositCredentials(
            $clbResult->getUserId(),
            [$cryptoNetwork]
        )->getAddress($cryptoNetwork->getSymbol());

        $depositAddress = strtolower($depositAddress);

        if ($depositAddress !== strtolower($clbResult->getAddress())) {
            throw new ConsumerException(
                'Invalid message received.' .
                'Expect deposit address ' . $clbResult->getAddress() .
                ' received '. $depositAddress
            );
        }

        $depositAmount = $this->moneyWrapper->parse($clbResult->getAmount(), $tradable->getMoneySymbol());

        // in case forwarded amount is different that the deposit amount (if token has transfer fee for example)
        $forwardedAmount = $clbResult->getForwardedAmount()
            ? $this->moneyWrapper->parse($clbResult->getForwardedAmount(), $tradable->getMoneySymbol())
            : null;

        $eAmount = $this->getTotalAmount($tradable, $eTransactions, $depositAddress);

        if (!$depositAmount->equals($eAmount) && ($forwardedAmount && !$forwardedAmount->equals($eAmount))) {
            throw new ConsumerException(
                'Invalid message received. ' .
                'Expect Transaction amount ' . $this->moneyWrapper->format($depositAmount) .
                ' received '. $this->moneyWrapper->format($eAmount)
            );
        }
    }

    /*
     * Checks against gateway db if the info is right
     */
    private function assertGatewayDeposit(DepositCallbackMessage $clbResult): void
    {
        $validDeposit = $this->depositCommunicator->validateDeposit($clbResult);

        if (!$validDeposit->getStatus()) {
            throw new ConsumerException(
                'Invalid message received ' .
                'Deposit is invalid in gateway side: '. $validDeposit->getErrorMessage()
            );
        }
    }

    /**
     * @param BlockchainTransaction[] $eTransactions
     */
    private function getTotalAmount(
        TradableInterface $tradable,
        array $eTransactions,
        string $depositAddress
    ): Money {
        $total = $this->moneyWrapper->parse('0', $tradable->getMoneySymbol());

        foreach ($eTransactions as $eTransaction) {
            $amount = $this->moneyWrapper->parse(
                $eTransaction->getToAmounts()[$depositAddress] ?? '0',
                $tradable->getMoneySymbol()
            );

            $total = $total->add($amount);
        }

        return $total;
    }

    private function getDepositStrategyContext(
        TradableInterface $tradable,
        Crypto $cryptoNetwork
    ): DepositContext {
        $strategy = $tradable instanceof Token
            ? new DepositTokenStrategy(
                $this->balanceHandler,
                $this->wallet,
                $this->moneyWrapper,
                $this->cryptoManager,
                $cryptoNetwork
            )
            : new DepositCryptoStrategy($this->balanceHandler, $this->moneyWrapper);

        return new DepositContext($strategy);
    }

    private function saveDepositHash(
        array $hashes,
        User $user,
        TradableInterface $tradable,
        Crypto $cryptoNetwork
    ): void {
        foreach ($hashes as $hash) {
            $depositHash = new DepositHash();

            $depositHash->setHash(strtolower($hash))
                ->setCrypto($tradable instanceof Crypto ? $tradable : $cryptoNetwork)
                ->setToken($tradable instanceof Token ? $tradable : null)
                ->setUser($user);

            $this->em->persist($depositHash);
            $this->em->flush();
        }
    }
}
