<?php declare(strict_types = 1);

namespace App\Exchange;

use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\User;
use App\Exchange\Trade\TradeResult;
use App\Wallet\Money\MoneyWrapperInterface;

interface ExchangerInterface
{
    public function placeOrder(
        User $user,
        Market $market,
        string $amountInput,
        string $priceInput,
        bool $marketPrice,
        int $side,
        MoneyWrapperInterface $moneyWrapper,
        CryptoRatesFetcherInterface $cryptoRatesFetcher
    ): TradeResult;

    public function cancelOrder(Market $market, Order $order): TradeResult;
}
