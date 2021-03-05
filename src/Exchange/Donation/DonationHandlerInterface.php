<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Donation\Model\CheckDonationResult;
use App\Exchange\Market;
use Money\Money;

interface DonationHandlerInterface
{
    public function checkDonation(
        Market $market,
        string $currency,
        string $amount,
        User $donorUser
    ): CheckDonationResult;

    public function makeDonation(
        Market $market,
        string $currency,
        string $amount,
        string $expectedAmountUser,
        User $donorUser,
        string $sellOrdersSummary
    ): Donation;

    public function getTokensWorth(string $sellOrdersWorth, string $currency): string;

    public function saveDonation(
        User $donor,
        User $tokenCreator,
        string $currency,
        Money $amount,
        Money $feeAmount,
        Money $tokenAmount,
        Token $token
    ): Donation;
}
