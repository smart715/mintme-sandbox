<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\PendingWithdrawInterface;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use Money\Money;

interface PendingManagerInterface
{
    public function create(
        User $user,
        Address $address,
        Amount $amount,
        TradableInterface $tradable,
        Money $fee,
        Crypto $cryptoNetwork
    ): PendingWithdrawInterface;

    public function getPendingTokenWithdraw(
        User $user,
        int $offset,
        int $limit
    ): array;

    public function getPendingCryptoWithdraw(
        User $user,
        int $offset,
        int $limit
    ): array;
}
