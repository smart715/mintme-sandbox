<?php declare(strict_types = 1);

namespace App\Wallet;

use App\Entity\Crypto;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\Config;
use App\Manager\PendingManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Transaction;
use App\Wallet\Withdraw\WithdrawGatewayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Throwable;

class Wallet implements WalletInterface
{
    /** @var WithdrawGatewayInterface */
    private $withdrawGateway;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var DepositGatewayCommunicator */
    private $depositCommunicator;

    /** @var ContractHandlerInterface */
    private $contractHandler;

    /** @var PendingManagerInterface */
    private $pendingManager;

    /** @var EntityManagerInterface */
    private $em;

    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        WithdrawGatewayInterface $withdrawGateway,
        BalanceHandlerInterface $balanceHandler,
        DepositGatewayCommunicator $depositCommunicator,
        ContractHandlerInterface $contractHandler,
        PendingManagerInterface $pendingManager,
        EntityManagerInterface $em,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->withdrawGateway = $withdrawGateway;
        $this->balanceHandler = $balanceHandler;
        $this->depositCommunicator = $depositCommunicator;
        $this->contractHandler = $contractHandler;
        $this->pendingManager = $pendingManager;
        $this->em = $em;
        $this->config = $config;
        $this->logger = $logger;
    }

    /** {@inheritdoc} */
    public function getWithdrawDepositHistory(User $user, int $offset, int $limit): array
    {
        $limit = intval($limit / 2);

        $depositHistory = $this->depositCommunicator->getTransactions($user, $offset, $limit);
        $withdrawHistory = $this->withdrawGateway->getHistory($user, $offset, $limit);

        $history = array_merge($depositHistory, $withdrawHistory);

        usort($history, function (Transaction $first, Transaction $second): bool {
            return $first->getDate()->getTimestamp() < $second->getDate()->getTimestamp();
        });

        return $history;
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
        // TODO: convert float fee to currency
        $fee = $tradable instanceof Crypto
            ? $tradable->getFee()
            : new Money((int)$this->config->getTokenWithdrawFee(), new Currency('TOK'));

        if ($tradable instanceof Crypto) {
            $crypto = $tradable;
            $tradable = Token::getFromCrypto($tradable);
        }

        $available = $this->balanceHandler->balance($user, $tradable)->getAvailable();
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

        if (isset($crypto) && !$this->validateAmount($crypto, $amount, $user)) {
            throw new NotEnoughAmountException();
        }

        $this->balanceHandler->withdraw($user, $tradable, $amount->getAmount()->add($fee));

        return $this->pendingManager->create($user, $address, $amount, $tradable);
    }

    /** @throws Throwable */
    public function withdrawCommit(PendingWithdraw $pendingWithdraw): void
    {
        $crypto = $pendingWithdraw->getCrypto();
        $user = $pendingWithdraw->getUser();
        $amount = $pendingWithdraw->getAmount();
        $address = $pendingWithdraw->getAddress();

        if (!$this->validateAmount($crypto, $amount, $user)) {
            throw new NotEnoughAmountException();
        }

        $this->em->beginTransaction();

        try {
            $this->withdrawGateway->withdraw($user, $amount->getAmount(), $address->getAddress(), $crypto);
            $this->em->remove($pendingWithdraw);
            $this->em->flush();
        } catch (Throwable $exception) {
            $this->em->rollback();

            $this->logger->error(
                "Failed to pay '{$user->getEmail()}' amount {$amount->getAmount()->getAmount()} {$crypto->getSymbol()}.
                Withdraw-gateway failed with the next errror: {$exception->getMessage()}. Payment has been rollbacked"
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
        }, $this->depositCommunicator->getDepositCredentials($user->getId(), array_map(function ($crypto) {
            return Token::getFromCrypto($crypto);
        }, $cryptos))->toArray());
    }

    /** {@inheritDoc} */
    public function getTokenDepositCredentials(User $user): array
    {
        return array_map(function (string $address) {
            return new Address($address);
        }, $this->contractHandler->getDepositCredentials($user)->toArray());
    }

    /** {@inheritDoc} */
    public function getDepositCredential(User $user, Crypto $crypto): Address
    {
        return $this->getDepositCredentials($user, [$crypto])[$crypto->getSymbol()];
    }

    public function getFee(Crypto $crypto): Money
    {
        return $this->depositCommunicator->getFee($crypto->getSymbol());
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
}
