<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\TradableInterface;
use App\Entity\User;

interface BalanceStrategyInterface
{
    public function deposit(User $user, TradableInterface $tradable, string $amount): void;
}
