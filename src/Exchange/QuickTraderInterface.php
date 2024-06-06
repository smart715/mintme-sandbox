<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\User;
use App\Exchange\Trade\CheckTradeResult;
use App\Exchange\Trade\TradeResult;

interface QuickTraderInterface
{
    public function makeSell(User $user, Market $market, string $amount, string $expectedAmount): TradeResult;
    public function makeBuy(User $user, Market $market, string $amount, string $expectedAmount): TradeResult;

    public function checkSell(Market $market, string $amount): CheckTradeResult;
    public function checkSellReversed(Market $market, string $amountToReceive): CheckTradeResult;
    public function checkBuy(Market $market, string $amount): CheckTradeResult;
    public function checkBuyReversed(Market $market, string $amountToReceive): CheckTradeResult;
    public function checkDonationReversed(Market $market, string $amountToReceive): CheckTradeResult;
}
