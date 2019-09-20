<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\TradebleInterface;
use App\Entity\User;

class BalanceContext
{
    /** @var BalanceStrategyInterface */
    private $strategy;

    public function __construct(BalanceStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function doDeposit(TradebleInterface $tradeble, User $user, string $amount): void
    {
        $this->strategy->deposit($user, $tradeble, $amount);
    }
}
