<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Entity\Crypto;
use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Trade\CheckTradeResult;
use Money\Money;

interface DonationHandlerInterface
{
    public function checkDonation(
        Market $market,
        string $amount,
        User $donorUser
    ): CheckTradeResult;

    public function makeDonation(
        Market $market,
        string $donationAmountInCrypto,
        string $expectedAmountUser,
        User $donorUser
    ): Donation;

    public function saveDonation(
        User $donor,
        User $tokenCreator,
        string $currency,
        Money $amount,
        Money $feeAmount,
        Money $tokenAmount,
        Token $token,
        Money $receiverAmount,
        Money $receiverFeeAmount,
        string $receiverCurrency,
        string $type
    ): Donation;
}
