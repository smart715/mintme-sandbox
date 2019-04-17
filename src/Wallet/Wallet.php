<?php declare(strict_types = 1);

namespace App\Wallet;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Transaction;
use App\Wallet\Withdraw\WithdrawGatewayInterface;
use Exception;
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

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        WithdrawGatewayInterface $withdrawGateway,
        BalanceHandlerInterface $balanceHandler,
        DepositGatewayCommunicator $depositCommunicator,
        LoggerInterface $logger
    ) {
        $this->withdrawGateway = $withdrawGateway;
        $this->balanceHandler = $balanceHandler;
        $this->depositCommunicator = $depositCommunicator;
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

    /** @throws Throwable */
    public function withdraw(User $user, Address $address, Amount $amount, Crypto $crypto): void
    {
        $token = Token::getFromCrypto($crypto);
        $available = $this->balanceHandler->balance($user, $token)->getAvailable();
        $this->logger->debug(
            "Created a new withdraw request for '{$user->getEmail()}' to 
            send {$amount->getAmount()->getAmount()} {$crypto->getSymbol()} on {$address->getAddress()}"
        );

        if ($available->lessThan($amount->getAmount()->add($crypto->getFee()))) {
            $this->logger->warning(
                "Requested balance for user '{$user->getEmail()}'. 
                Not enough amount to pay {$amount->getAmount()->getAmount()} {$crypto->getSymbol()}
                Available amount: {$available->getAmount()} {$crypto->getSymbol()}"
            );

            throw new NotEnoughUserAmountException();
        }

        $balance = $this->withdrawGateway->getBalance($crypto);

        if ($balance->lessThan($amount->getAmount()->add($crypto->getFee()))) {
            $this->logger->warning(
                "Requested withdraw-gateway balance to pay '{$user->getEmail()}'.
                Not enough amount to pay {$amount->getAmount()->getAmount()} {$crypto->getSymbol()}
                Available withdraw amount: {$balance->getAmount()} {$crypto->getSymbol()}"
            );

            throw new NotEnoughAmountException();
        }

        $this->balanceHandler->withdraw($user, $token, $amount->getAmount()->add($crypto->getFee()));

        try {
            $this->withdrawGateway->withdraw($user, $amount->getAmount(), $address->getAddress(), $crypto);
        } catch (Throwable $exception) {
            $this->logger->error(
                "Failed to pay '{$user->getEmail()}' amount {$amount->getAmount()->getAmount()} {$crypto->getSymbol()}.
                Withdraw-gateway failed with the next errror: {$exception->getMessage()}. Trying to rollback payment.."
            );

            try {
                $this->balanceHandler->deposit($user, $token, $amount->getAmount()->add($crypto->getFee()));
            } catch (Throwable $exception) {
                $this->logger->critical(
                    "Failed to rollback payment. Requires to do it manually.
                    User: {$user->getEmail()}; 
                    Amount: {$amount->getAmount()->getAmount()}; 
                    Crypto: {$crypto->getSymbol()}.
                    Reason: {$exception->getMessage()}"
                );
            }

            throw new Exception();
        }
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
    public function getDepositCredential(User $user, Crypto $crypto): Address
    {
        return $this->getDepositCredentials($user, [$crypto])[$crypto->getSymbol()];
    }
}
