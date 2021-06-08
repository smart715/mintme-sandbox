<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Entity\User;
use App\Exchange\Trade\TradeResult;

interface ExchangerInterface
{
    public function placeOrder(
        User $user,
        Market $market,
        string $amountInput,
        string $priceInput,
        bool $marketPrice,
        int $side
    ): TradeResult;

    public function executeOrder(
        User $user,
        Market $market,
        string $amountInput,
        string $expectedToReceive,
        int $side
    ): TradeResult;

    public function cancelOrder(Market $market, Order $order): TradeResult;
}
