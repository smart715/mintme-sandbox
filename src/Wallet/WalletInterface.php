<?php

namespace App\Wallet;

use App\Entity\Crypto;
use App\Entity\User;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;

interface WalletInterface
{
    /**
     * @throws \Throwable
     * @throws NotEnoughAmountException
     * @throws NotEnoughUserAmountException
     */
    public function withdraw(User $user, Address $address, Amount $amount, Crypto $crypto): void;
}
