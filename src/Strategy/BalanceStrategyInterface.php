<?php declare(strict_types = 1);

namespace App\Strategy;

use App\Entity\TradebleInterface;
use App\Entity\User;

interface BalanceStrategyInterface
{
    public function deposit(User $user, TradebleInterface $tradeble, string $amount): void;
}
