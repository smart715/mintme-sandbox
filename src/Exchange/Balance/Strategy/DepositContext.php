<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\TradableInterface;
use App\Entity\User;

class DepositContext
{
    /** @var BalanceStrategyInterface */
    private $strategy;

    public function __construct(BalanceStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function doDeposit(TradableInterface $tradable, User $user, string $amount): void
    {
        $this->strategy->deposit($user, $tradable, $amount);
    }
}
