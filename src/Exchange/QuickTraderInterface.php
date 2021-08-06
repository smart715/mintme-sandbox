<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\User;
use App\Exchange\Trade\CheckTradeResult;
use App\Exchange\Trade\TradeResult;

interface QuickTraderInterface
{
    public function makeSell(User $user, Market $market, string $amount, string $expectedAmount): TradeResult;

    public function checkSell(Market $market, string $amount): CheckTradeResult;
}
