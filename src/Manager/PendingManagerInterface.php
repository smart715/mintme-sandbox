<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\PendingWithdrawInterface;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use Money\Money;

interface PendingManagerInterface
{
    public function create(User $user, Address $address, Amount $amount, TradebleInterface $tradable, Money $fee): PendingWithdrawInterface;
}
