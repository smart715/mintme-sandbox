<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Entity\User;
use App\Exchange\Market;

interface DonationHandlerInterface
{
    public function checkDonation(Market $market, string $currency, string $amount, string $fee): string;
    public function makeDonation(
        Market $market,
        string $currency,
        string $amount,
        string $fee,
        string $expectedAmountUser,
        User $donorUser
    ): void;
}
