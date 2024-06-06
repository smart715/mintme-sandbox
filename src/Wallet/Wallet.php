<?php declare(strict_types = 1);

namespace App\Wallet;

use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Events\DepositCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\DepositContext;
use App\Exchange\Balance\Strategy\DepositCryptoStrategy;
use App\Exchange\Balance\Strategy\DepositTokenStrategy;
use App\Exchange\Config\TokenConfig;
use App\Logger\WithdrawLogger;
use App\Manager\CryptoManager;
use App\Manager\InternalTransactionManagerInterface;
use App\Manager\PendingManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\Repository\UserRepository;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Exception\IncorrectAddressException;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Exception\TokenTransfersPausedException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\WithdrawInfo;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\WithdrawGatewayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

class Wallet implements WalletInterface
{
    public const GATEWAY_LIMIT_DEFAULT = 100;

    private const ADDRESS_LENGTH = 42;

    private WithdrawGatewayInterface $withdrawGateway;

    private BalanceHandlerInterface $balanceHandler;

    private DepositGatewayCommunicator $depositCommunicator;

    private PendingManagerInterface $pendingManager;

    private EntityManagerInterface $em;

    private ContractHandlerInterface $contractHandler;

    private TokenManagerInterface $tokenManager;

    private TokenConfig $tokenConfig;

    private RebrandingConverterInterface $rebrandingConverter;

    private ValidatorFactoryInterface $vf;

    private UserRepository $userRepository;

    private MoneyWrapperInterface $moneyWrapper;

    private CryptoManager $cryptoManager;

    private EventDispatcherInterface $eventDispatcher;

    private InternalTransactionManagerInterface $internalTransactionManager;

    private HideFeaturesConfig $hideFeaturesConfig;

    private WithdrawLogger $withdrawLogger;

    private WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager;

    public function __construct(
        WithdrawGatewayInterface $withdrawGateway,
        BalanceHandlerInterface $balanceHandler,
        DepositGatewayCommunicator $depositCommunicator,
        PendingManagerInterface $pendingManager,
        EntityManagerInterface $em,
        ContractHandlerInterface $contractHandler,
        TokenManagerInterface $tokenManager,
        TokenConfig $tokenConfig,
        RebrandingConverterInterface $rebrandingConverter,
        ValidatorFactoryInterface $vf,
        UserRepository $userRepository,
        MoneyWrapperInterface $moneyWrapper,
        CryptoManager $cryptoManager,
        EventDispatcherInterface $eventDispatcher,
        InternalTransactionManagerInterface $internalTransactionManager,
        HideFeaturesConfig $hideFeaturesConfig,
        WithdrawLogger $withdrawLogger,
        WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager
    ) {
        $this->withdrawGateway = $withdrawGateway;
        $this->balanceHandler = $balanceHandler;
        $this->depositCommunicator = $depositCommunicator;
        $this->pendingManager = $pendingManager;
        $this->em = $em;
        $this->contractHandler = $contractHandler;
        $this->tokenManager = $tokenManager;
        $this->tokenConfig = $tokenConfig;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->vf = $vf;
        $this->userRepository = $userRepository;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoManager = $cryptoManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->internalTransactionManager = $internalTransactionManager;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
        $this->withdrawLogger = $withdrawLogger;
        $this->wrappedCryptoTokenManager = $wrappedCryptoTokenManager;
    }

    /** {@inheritdoc} */
    public function getWithdrawDepositHistory(User $user, int $offset, int $limit): array
    {
        // todo: store transactions in mintme DB to make pagination more efficient
        $gatewayLimit = $offset + $limit + self::GATEWAY_LIMIT_DEFAULT;

        $depositHistory = $this->depositCommunicator->getTransactions($user, 0, $gatewayLimit);
        $withdrawHistory = $this->withdrawGateway->getHistory($user, 0, $gatewayLimit);
        $withdrawTokenPending = $this->pendingManager->getPendingTokenWithdraw($user, 0, $gatewayLimit);
        $withdrawCryptoPending = $this->pendingManager->getPendingCryptoWithdraw($user, 0, $gatewayLimit);
        $tokenTransactionHistory = $this->contractHandler->getTransactions($user, 0, $gatewayLimit);
        $internalTransactionHistory = $this->internalTransactionManager->getLatest($user, 0, $gatewayLimit);

        $history = array_merge(
            $depositHistory,
            $withdrawHistory,
            $withdrawTokenPending,
            $withdrawCryptoPending,
            $tokenTransactionHistory,
            $internalTransactionHistory
        );

        $history = array_filter($history, function ($transaction) {
            $tradable = $transaction->getTradable();

            if ($tradable) {
                $symbol = $tradable instanceof Token && $tradable->getCrypto()
                    ? $tradable->getCrypto()->getSymbol()
                    : $tradable->getSymbol();

                return $this->hideFeaturesConfig->isCryptoEnabled($symbol);
            }

            return false;
        });

        usort($history, function (Transaction $first, Transaction $second) {
            return $second->getDate()->getTimestamp() - $first->getDate()->getTimestamp();
        });

        return array_slice($history, $offset, $limit);
    }

    /**
     * @param Crypto|Token $tradable
     * @throws Throwable
     */
    public function withdrawInit(
        User $user,
        Address $address,
        Amount $amount,
        TradableInterface $tradable,
        Crypto $cryptoNetwork
    ): PendingWithdrawInterface {
        $receiverUserId = $this->getMintmeUserId($address->getAddress(), $cryptoNetwork->getSymbol());
        $isInternalWithdraw = null !== $receiverUserId;
        $withdrawFee = $this->getWithdrawFee($tradable, $cryptoNetwork, $isInternalWithdraw);

        $isFeeInTradable = $withdrawFee->getCurrency()->getCode() === $tradable->getMoneySymbol();

        $amountPlusFee = $amount->getAmount();

        if ($isFeeInTradable) {
            $amountPlusFee = $amountPlusFee->add($withdrawFee);
        }

        $validator = $this->vf->createAddressValidator($cryptoNetwork, $address->getAddress());
        $cryptoNetworkSymbol = $cryptoNetwork->getSymbol();

        if (in_array($cryptoNetworkSymbol, Symbols::ETH_BASED, true)) {
            $this->validateEthereumWallet($address->getAddress(), $cryptoNetworkSymbol);
        } elseif (!$validator->validate()) {
            throw new IncorrectAddressException("Invalid address", $address->getAddress());
        }

        $balanceResult = $this->balanceHandler->balance($user, $tradable);

        if ($tradable instanceof Token) {
            $this->assertTokenTransfersEnabled($tradable, $cryptoNetwork);

            $balanceResult = $this->tokenManager->getRealBalance(
                $tradable,
                $this->balanceHandler->balance($user, $tradable),
                $user
            );
        }

        $available = $balanceResult->getAvailable();
        $this->withdrawLogger->info(
            "Created a new withdraw request for '{$user->getEmail()}' to " .
            "send {$amount->getAmount()->getAmount()} {$tradable->getSymbol()} on {$address->getAddress()} " .
            ($isInternalWithdraw ? "to internal user with id {$receiverUserId}" : "to external address")
        );

        if ($available->lessThan($amountPlusFee)) {
            $this->withdrawLogger->warning(
                "Requested balance for user '{$user->getEmail()}'.
                Not enough amount to pay {$amountPlusFee->getAmount()} {$tradable->getSymbol()}
                Available amount: {$available->getAmount()} {$tradable->getSymbol()}"
            );

            throw new NotEnoughUserAmountException();
        }

        if (!$isFeeInTradable && !$this->validateTokenFee($user, $cryptoNetwork, $withdrawFee)) {
            throw new NotEnoughAmountException(
                'You do not have enough ' . $this->rebrandingConverter->convert($cryptoNetwork->getSymbol())
            );
        }

        try {
            $this->balanceHandler->beginTransaction();

            $withdrawView = $this->balanceHandler->withdraw($user, $tradable, $amountPlusFee);
            $absolute = $withdrawView->getChange()->absolute();

            $withdrawBlockchainAmount = new Amount($isFeeInTradable ? $absolute->subtract($withdrawFee) : $absolute);

            // if tradable is token and has fee
            if (!$isFeeInTradable && !$withdrawFee->isZero()) {
                $this->balanceHandler->withdraw(
                    $user,
                    $cryptoNetwork,
                    $withdrawFee
                );
            }
        } catch (Throwable $e) {
            $this->balanceHandler->rollback();
            $this->withdrawLogger->error($e->getMessage(), [
                'isInternalWithdraw' => $isInternalWithdraw,
                'sender' => $user->getEmail(),
                'receiverUserId' => $receiverUserId,
            ]);

            throw $e;
        }

        return $this->pendingManager->create(
            $user,
            $address,
            $withdrawBlockchainAmount,
            $tradable,
            $withdrawFee,
            $cryptoNetwork
        );
    }

    /**
     * @param PendingWithdraw|PendingTokenWithdraw $pendingWithdraw
     * @throws Throwable
     */
    public function withdrawCommit(
        PendingWithdrawInterface $pendingWithdraw
    ): void {
        $mintmeUserId = $this->getMintmeUserId(
            $pendingWithdraw->getAddress()->getAddress(),
            $pendingWithdraw->getCryptoNetwork()->getSymbol()
        );
        $isInternalWithdraw = null !== $mintmeUserId;
        $tradable = $pendingWithdraw instanceof PendingWithdraw
            ? $pendingWithdraw->getCrypto()
            : $pendingWithdraw->getToken();

        $cryptoNetwork = $pendingWithdraw->getCryptoNetwork();

        $user = $pendingWithdraw->getUser();
        $amount = $pendingWithdraw->getAmount();
        $address = $pendingWithdraw->getAddress();

        $this->em->beginTransaction();

        try {
            $isCryptoToken = $tradable instanceof Crypto && (
                $tradable->isToken() || // USDC example
                $cryptoNetwork->getId() !== $tradable->getId() // Wrapped Mintme
            );

            if ($isInternalWithdraw) {
                $this->doInternalWithdraw(
                    $user,
                    $amount,
                    $tradable,
                    $address,
                    $mintmeUserId,
                    $cryptoNetwork,
                    $pendingWithdraw
                );
            } else {
                $this->withdrawLogger->info(
                    "External Withdraw for user '{$user->getEmail()}' " .
                    "send {$amount->getAmount()->getAmount()} {$tradable->getSymbol()} on {$address->getAddress()}"
                );

                $wrappedCrypto = $tradable instanceof Crypto
                    ? $this->wrappedCryptoTokenManager->findByCryptoAndDeploy($tradable, $cryptoNetwork)
                    : null;
                $isNetworkWithNativeTradable = $wrappedCrypto && $wrappedCrypto->isNative();

                if (($tradable instanceof Token || $isCryptoToken) && !$isNetworkWithNativeTradable) {
                    if ($tradable instanceof Token) {
                        $this->assertTokenTransfersEnabled($tradable, $cryptoNetwork);
                    }

                    $this->contractHandler->withdraw(
                        $user,
                        $amount->getAmount(),
                        $address->getAddress(),
                        $tradable,
                        $cryptoNetwork,
                        $pendingWithdraw->getFee()
                    );
                } else {
                    /** @var Crypto $tradable */
                    $this->withdrawGateway->withdraw(
                        $user,
                        $amount->getAmount(),
                        $address->getAddress(),
                        $isNetworkWithNativeTradable ? $cryptoNetwork : $tradable,
                        $pendingWithdraw->getFee()
                    );
                }
            }

            $this->em->remove($pendingWithdraw);
            $this->em->flush();
        } catch (Throwable $exception) {
            $this->em->rollback();

            $this->withdrawLogger->error(
                "Failed to pay '{$user->getEmail()}' amount ".
                "{$amount->getAmount()->getAmount()} {$tradable->getSymbol()}" .
                ($isInternalWithdraw
                    ? "to internal user with id {$mintmeUserId}"
                    : "to external address on {$address->getAddress()}").
                "Withdraw-gateway failed with the next error: {$exception->getMessage()}"
            );

            throw $exception;
        }

        $this->em->commit();
    }

    /** {@inheritDoc} */
    public function getDepositCredentials(User $user, array $cryptos): array
    {
        return array_map(function (string $address) {
            return new Address($address);
        }, $this->depositCommunicator->getDepositCredentials($user->getId(), $cryptos)->toArray());
    }

    /** {@inheritDoc} */
    public function getTokenDepositCredentials(User $user): array
    {
        $addresses = $this->contractHandler->getDepositCredentials($user);

        foreach ($addresses as $symbol => $address) {
            $addresses[$symbol] = new Address($address);
        }

        return $addresses;
    }

    /** {@inheritDoc} */
    public function getDepositCredential(User $user, Crypto $crypto): Address
    {
        return $this->getDepositCredentials($user, [$crypto])[$crypto->getSymbol()];
    }

    public function getDepositInfo(TradableInterface $tradable, Crypto $cryptoNetwork, ?User $user = null): ?DepositInfo
    {
        $wrappedToken = $tradable instanceof Crypto
            ? $this->wrappedCryptoTokenManager->findByCryptoAndDeploy($tradable, $cryptoNetwork)
            : null;
        $isCryptoNetwork = $wrappedToken && $wrappedToken->isNative();

        $isCryptoToken = $tradable instanceof Crypto && (
            $tradable->isToken() || // USDC example
            $cryptoNetwork->getId() !== $tradable->getId() // Wrapped Mintme
        ) && (!$wrappedToken ||  $wrappedToken->getAddress());

        if ($tradable instanceof Token || $isCryptoToken) {
            return $this->contractHandler->getDepositInfo($tradable, $cryptoNetwork);
        }

        return $this->depositCommunicator->getDepositInfo($isCryptoNetwork ? $cryptoNetwork : $tradable, $user);
    }

    public function getWithdrawInfo(Crypto $cryptoNetwork, TradableInterface $tradable): WithdrawInfo
    {
        try {
            return $this->contractHandler->getWithdrawInfo($cryptoNetwork, $tradable);
        } catch (Throwable $e) {
            $this->withdrawLogger->error(
                "Failed to get withdraw info for '{$tradable->getSymbol()}' on '{$cryptoNetwork->getSymbol()}'",
                [
                   'exception' => $e->getMessage(),
                ]
            );

            return new WithdrawInfo(new Money('0', new Currency($cryptoNetwork->getMoneySymbol())), false);
        }
    }

    private function getMintmeUserId(string $address, string $cryptoNetwork): ?int
    {
        return $this->withdrawGateway->getUserId($address, $cryptoNetwork);
    }

    private function validateTokenFee(User $user, Crypto $crypto, Money $tokenFee): bool
    {
        $balance = $this->balanceHandler->balance($user, $crypto);

        if ($balance->getAvailable()->lessThan($tokenFee)) {
            $this->withdrawLogger->warning(
                "Requested withdraw-gateway balance to pay '{$user->getEmail()}'. Not enough amount to pay fee"
            );

            return false;
        }

        return true;
    }

    private function validateEthereumWallet(string $address, string $cryptoSymbol): void
    {
        if (!$this->startsWith($address, '0x')) {
            throw new IncorrectAddressException('incorrect address start', $address);
        }

        if (self::ADDRESS_LENGTH !== strlen($address)) {
            throw new IncorrectAddressException('incorrect address length', $address);
        }

        if ($this->withdrawGateway->isContractAddress($address, $cryptoSymbol)) {
            throw new IncorrectAddressException('smart contract address given', $address);
        }
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    private function getWithdrawFee(TradableInterface $tradable, Crypto $crypto, bool $isInternalWithdraw): Money
    {
        $desiredFee = $this->getDesiredWithdrawFee($tradable, $crypto, $isInternalWithdraw);

        if ($isInternalWithdraw ||
            !$desiredFee->isSameCurrency(new Money(0, new Currency($crypto->getMoneySymbol())))
        ) {
            return $desiredFee;
        }

        // avoid losing money on withdraw, in case network fee is higher than desired fee
        $minAllowedFee = $this->getWithdrawInfo($crypto, $tradable)->getMinFee();

        return $desiredFee->greaterThan($minAllowedFee)
            ? $desiredFee
            : $minAllowedFee;
    }

    private function getDesiredWithdrawFee(TradableInterface $tradable, Crypto $crypto, bool $isInternalWithdraw): Money
    {
        if ($isInternalWithdraw &&
            $tradable instanceof Crypto &&
            null !== $internalFee = $this->tokenConfig->getCryptoInternalWithdrawFeeBySymbol($tradable->getSymbol())
        ) {
            return $internalFee;
        }

        if ($tradable instanceof Crypto && $wrappedToken = $tradable->getWrappedTokenByCrypto($crypto)) {
            return $wrappedToken->getFee();
        }

        if ($tradableFee = $tradable->getFee()) {
            return $tradableFee;
        }

        if ($crypto->getFee() && $crypto->getFee()->isSameCurrency(new Money(0, new Currency(Symbols::WEB)))) {
            $mintmeFee = $this->tokenConfig->getWithdrawFeeByCryptoSymbol(Symbols::WEB, $isInternalWithdraw);

            if ($mintmeFee) {
                return $mintmeFee;
            }

            return $crypto->getFee();
        }

        if ($tradable instanceof Token) {
            $tokenFee = $this->tokenConfig->getWithdrawFeeByCryptoSymbol(
                $crypto->getSymbol(),
                $isInternalWithdraw,
                $crypto->getMoneySymbol()
            );

            if ($tokenFee) {
                return $tokenFee;
            }
        }

        return $this->tokenConfig->getWithdrawFeeByCryptoSymbol(Symbols::ETH, $isInternalWithdraw);
    }

    private function doInternalWithdraw(
        User $user,
        Amount $amount,
        TradableInterface $tradable,
        Address $address,
        ?int $mintmeUserId,
        Crypto $cryptoNetwork,
        PendingWithdrawInterface $pendingWithdraw
    ): void {
        $this->withdrawLogger->info(
            "Internal withdraw for user '{$user->getEmail()}' " .
            "send {$amount->getAmount()->getAmount()} {$tradable->getSymbol()} on {$address->getAddress()}"
        );

        /** @var User $targetUser */
        $targetUser = $this->userRepository->find($mintmeUserId);

        $strategy = $tradable instanceof Token
            ? new DepositTokenStrategy(
                $this->balanceHandler,
                $this,
                $this->moneyWrapper,
                $this->cryptoManager,
                $cryptoNetwork
            )
            : new DepositCryptoStrategy($this->balanceHandler, $this->moneyWrapper);

        $balanceContext = new DepositContext($strategy);

        $this->balanceHandler->beginTransaction();

        $balanceContext->doDeposit(
            $tradable,
            $targetUser,
            $withdrawAmount = $this->moneyWrapper->format($amount->getAmount())
        );

        $internalTransfer = $this->internalTransactionManager->transferFunds(
            $pendingWithdraw->getUser(),
            $targetUser,
            $tradable,
            $pendingWithdraw->getCryptoNetwork(),
            $pendingWithdraw->getAmount(),
            $pendingWithdraw->getAddress(),
            $pendingWithdraw->getFee(),
        );

        $cryptoNetworkName = $this->cryptoManager->getNetworkName($cryptoNetwork->getSymbol());

        $this->em->persist($internalTransfer->getInternalWithdrawal());
        $this->em->persist($internalTransfer->getInternalDeposit());
        $this->emitTransactionEvents($tradable, $user, $withdrawAmount, $pendingWithdraw, $targetUser, $cryptoNetworkName);
    }

    private function emitTransactionEvents(
        TradableInterface $tradable,
        User $user,
        string $withdrawAmount,
        PendingWithdrawInterface $pendingWithdraw,
        User $targetUser,
        string $cryptoNetworkName
    ): void {
        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new WithdrawCompletedEvent(
                $tradable,
                $user,
                $withdrawAmount,
                $pendingWithdraw->getAddress()->getAddress(),
                $cryptoNetworkName
            ),
            WithdrawCompletedEvent::NAME
        );

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new DepositCompletedEvent(
                $tradable,
                $targetUser,
                $withdrawAmount,
                $cryptoNetworkName,
                $pendingWithdraw->getAddress()->getAddress(),
            ),
            DepositCompletedEvent::NAME
        );
    }

    /** @throws TokenTransfersPausedException */
    private function assertTokenTransfersEnabled(Token $tradable, Crypto $cryptoNetwork): void
    {
        $withdrawInfo = $this->getWithdrawInfo($cryptoNetwork, $tradable);

        if ($withdrawInfo->isPaused()) {
            throw new TokenTransfersPausedException(sprintf(
                'Token transfers are paused for %s',
                $tradable->getSymbol()
            ));
        }
    }
}
