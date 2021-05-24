<?php declare(strict_types = 1);

namespace App\Wallet;

use App\Entity\Crypto;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exception\NotFoundTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\PendingManagerInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\Symbols;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Exception\IncorrectAddressException;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Model\Transaction;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\Withdraw\WithdrawGatewayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;

class Wallet implements WalletInterface
{
    private WithdrawGatewayInterface $withdrawGateway;

    private BalanceHandlerInterface $balanceHandler;

    private DepositGatewayCommunicator $depositCommunicator;

    private PendingManagerInterface $pendingManager;

    private EntityManagerInterface $em;

    private CryptoManagerInterface $cryptoManager;

    private ContractHandlerInterface $contractHandler;

    private LoggerInterface $logger;

    private TokenManagerInterface $tokenManager;

    private ParameterBagInterface $parameterBag;

    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        WithdrawGatewayInterface $withdrawGateway,
        BalanceHandlerInterface $balanceHandler,
        DepositGatewayCommunicator $depositCommunicator,
        PendingManagerInterface $pendingManager,
        EntityManagerInterface $em,
        CryptoManagerInterface $cryptoManager,
        ContractHandlerInterface $contractHandler,
        LoggerInterface $logger,
        TokenManagerInterface $tokenManager,
        ParameterBagInterface $parameterBag,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->withdrawGateway = $withdrawGateway;
        $this->balanceHandler = $balanceHandler;
        $this->depositCommunicator = $depositCommunicator;
        $this->pendingManager = $pendingManager;
        $this->em = $em;
        $this->cryptoManager = $cryptoManager;
        $this->contractHandler = $contractHandler;
        $this->logger = $logger;
        $this->tokenManager = $tokenManager;
        $this->parameterBag = $parameterBag;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** {@inheritdoc} */
    public function getWithdrawDepositHistory(User $user, int $offset, int $limit): array
    {
        // todo: store transactions in mintme DB to make pagination more efficient
        $gatewayLimit = $offset + $limit;

        $depositHistory = $this->depositCommunicator->getTransactions($user, 0, $gatewayLimit);
        $withdrawHistory = $this->withdrawGateway->getHistory($user, 0, $gatewayLimit);
        $tokenTransactionHistory = $this->contractHandler->getTransactions($this, $user, 0, $gatewayLimit);

        $history = array_merge($depositHistory, $withdrawHistory, $tokenTransactionHistory);

        usort($history, function (Transaction $first, Transaction $second) {
            return $first->getDate()->getTimestamp() < $second->getDate()->getTimestamp();
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
        TradebleInterface $tradable
    ): PendingWithdrawInterface {
        if ($tradable instanceof Crypto) {
            $crypto = $tradable;
            $token = (new Token())->setName($crypto->getSymbol());
        } else {
            $crypto = $this->cryptoManager->findBySymbol($tradable->getCryptoSymbol(), true);
            $token = $tradable;
        }

        $fee = $tradable->getFee() ?? new Money('0', new Currency(Symbols::TOK));
        $tokenEthFee = $this->moneyWrapper->parse(
            (string)$this->parameterBag->get('token_withdraw_fee'),
            Symbols::ETH
        );

        if (!$crypto) {
            throw new NotFoundTokenException();
        }

        $cryptoSymbol = $crypto->getSymbol();

        if (in_array($crypto->getSymbol(), [Symbols::ETH, Symbols::WEB], true)) {
            if (!$this->validateEtheriumAddress($address->getAddress()) ||
                !$this->withdrawGateway->isContractAddress($address->getAddress(), $cryptoSymbol)
            ) {
                throw new IncorrectAddressException();
            }
        }

        $available = $this->tokenManager->getRealBalance(
            $token,
            $this->balanceHandler->balance($user, $token)
        )->getAvailable();

        $this->logger->info(
            "Created a new withdraw request for '{$user->getEmail()}' to
            send {$amount->getAmount()->getAmount()} {$tradable->getSymbol()} on {$address->getAddress()}"
        );

        if ($available->lessThan($amount->getAmount()->add($fee))) {
            $this->logger->warning(
                "Requested balance for user '{$user->getEmail()}'.
                Not enough amount to pay {$amount->getAmount()->getAmount()} {$tradable->getSymbol()}
                Available amount: {$available->getAmount()} {$tradable->getSymbol()}"
            );

            throw new NotEnoughUserAmountException();
        }

        if ($tradable instanceof Crypto && !$tradable->isToken() && !$this->validateAmount($crypto, $amount, $user)) {
            throw new NotEnoughAmountException();
        } elseif (!$tradable->getFee() && !$this->validateTokenFee($user, $crypto, $tokenEthFee)) {
            throw new NotEnoughAmountException();
        }

        $this->balanceHandler->withdraw($user, $token, $amount->getAmount()->add($fee));

        if ($tradable instanceof Token && !$tradable->getFee()) {
            $this->balanceHandler->withdraw(
                $user,
                $crypto,
                Symbols::ETH === $crypto->getSymbol() ? $tokenEthFee : $crypto->getFee()
            );
        }

        return $this->pendingManager->create($user, $address, $amount, $tradable);
    }

    /**
     * @param PendingWithdraw|PendingTokenWithdraw $pendingWithdraw
     * @throws Throwable
     */
    public function withdrawCommit(PendingWithdrawInterface $pendingWithdraw): void
    {
        /** @var Crypto|Token $tradable */
        $tradable = $pendingWithdraw instanceof PendingWithdraw
            ? $pendingWithdraw->getCrypto()
            : $pendingWithdraw->getToken();
        $user = $pendingWithdraw->getUser();
        $amount = $pendingWithdraw->getAmount();
        $address = $pendingWithdraw->getAddress();

        if ($tradable instanceof Crypto && !$tradable->isToken() && !$this->validateAmount($tradable, $amount, $user)) {
            throw new NotEnoughAmountException();
        }

        $this->em->beginTransaction();

        try {
            if ($tradable instanceof Crypto && !$tradable->isToken()) {
                $this->withdrawGateway->withdraw($user, $amount->getAmount(), $address->getAddress(), $tradable);
            } else {
                $this->contractHandler->withdraw($user, $amount->getAmount(), $address->getAddress(), $tradable);
            }

            $this->em->remove($pendingWithdraw);
            $this->em->flush();
        } catch (Throwable $exception) {
            $this->em->rollback();

            $this->logger->error(
                "Failed to pay '{$user->getEmail()}' amount {$amount->getAmount()->getAmount()} {$tradable->getSymbol()}.
                Withdraw-gateway failed with the next error: {$exception->getMessage()}. Payment has been rollbacked"
            );

            throw new Exception();
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

    public function getDepositInfo(TradebleInterface $tradable): DepositInfo
    {
        $symbol = $tradable->getSymbol();

        return $tradable instanceof Crypto && !$tradable->isToken()
            ? $this->depositCommunicator->getDepositInfo($symbol)
            : $this->contractHandler->getDepositInfo($symbol);
    }

    private function validateTokenFee(User $user, ?Crypto $crypto, Money $tokenEthFee): bool
    {
        if (!$crypto) {
            throw new NotFoundTokenException();
        }

        $balance = $this->balanceHandler->balance($user, $crypto);

        if ($balance->getAvailable()->lessThan(
            Symbols::ETH === $crypto->getSymbol() ? $tokenEthFee : $crypto->getFee()
        )
        ) {
            $this->logger->warning(
                "Requested withdraw-gateway balance to pay '{$user->getEmail()}'. Not enough amount to pay fee"
            );

            return false;
        }

        return true;
    }

    private function validateAmount(Crypto $crypto, Amount $amount, User $user): bool
    {
        $balance = $this->withdrawGateway->getBalance($crypto);

        if ($balance->lessThan($amount->getAmount()->add($crypto->getFee()))) {
            $this->logger->warning(
                "Requested withdraw-gateway balance to pay '{$user->getEmail()}'.
                Not enough amount to pay {$amount->getAmount()->getAmount()} {$crypto->getSymbol()}
                Available withdraw amount: {$balance->getAmount()} {$crypto->getSymbol()}"
            );

            return false;
        }

        return true;
    }

    private function validateEtheriumAddress(string $address): bool
    {
        return $this->startsWith($address, '0x') && 42 === strlen($address);
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
