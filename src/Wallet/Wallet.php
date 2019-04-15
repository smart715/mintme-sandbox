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
use Throwable;

class Wallet implements WalletInterface
{
    /** @var WithdrawGatewayInterface */
    private $withdrawGateway;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var DepositGatewayCommunicator */
    private $depositCommunicator;

    public function __construct(
        WithdrawGatewayInterface $withdrawGateway,
        BalanceHandlerInterface $balanceHandler,
        DepositGatewayCommunicator $depositCommunicator
    ) {
        $this->withdrawGateway = $withdrawGateway;
        $this->balanceHandler = $balanceHandler;
        $this->depositCommunicator = $depositCommunicator;
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

        if ($this->balanceHandler->balance($user, $token)->getAvailable()->lessThan(
            $amount->getAmount()->add($crypto->getFee())
        )) {
            throw new NotEnoughUserAmountException();
        }

        if ($this->withdrawGateway->getBalance($crypto)->lessThan($amount->getAmount()->add($crypto->getFee()))) {
            throw new NotEnoughAmountException();
        }

        $this->balanceHandler->withdraw($user, $token, $amount->getAmount()->add($crypto->getFee()));

        try {
            $this->withdrawGateway->withdraw($user, $amount->getAmount(), $address->getAddress(), $crypto);
        } catch (Throwable $exception) {
            $this->balanceHandler->deposit($user, $token, $amount->getAmount()->add($crypto->getFee()));

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
