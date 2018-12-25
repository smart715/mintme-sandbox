<?php

namespace App\Wallet;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Withdraw\WithdrawGatewayInterface;
use Money\Currency;
use Money\Money;

class Wallet implements WalletInterface
{
    /** @var WithdrawGatewayInterface */
    private $withdrawGateway;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    public function __construct(
        WithdrawGatewayInterface $withdrawGateway,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->withdrawGateway = $withdrawGateway;
        $this->balanceHandler = $balanceHandler;
    }

    /** @throws \Throwable */
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

        $this->balanceHandler->withdraw($user, $token, $amount->getAmount());

        try {
            $this->withdrawGateway->withdraw($user, $amount->getAmount(), $address->getAddress(), $crypto);
        } catch (\Throwable $exception) {
            $this->balanceHandler->deposit($user, $token, $amount->getAmount());
            throw new \Exception();
        }
    }
}
