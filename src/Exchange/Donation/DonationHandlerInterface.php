<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Entity\User;
use App\Exchange\Market;
use Money\Money;

interface DonationHandlerInterface
{
    public function checkDonation(Market $market, string $currency, string $amount, User $donorUser): string;
    public function makeDonation(
        Market $market,
        string $currency,
        string $amount,
        string $expectedAmountUser,
        User $donorUser,
        Money $sellOrdersWorth
    ): void;
    public function getSellOrdersWorth(Money $sellOrdersWorth, string $currency): string;
    public function saveDonation(
        User $donor,
        User $tokenCreator,
        string $currency,
        Money $amount,
        Money $feeAmount,
        Money $tokenAmount
    ): void;
}
