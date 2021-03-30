<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Exchange\Donation\Model\CheckDonationResult;

interface DonationFetcherInterface
{
    public function checkDonation(
        string $marketName,
        string $amount,
        string $fee,
        int $tokenCreatorId
    ): CheckDonationResult;

    public function placeDonation(
        int $userId,
        string $market,
        string $amount,
        string $maxPrice,
        string $fee,
        int $tokenCreatorId
    ): CheckDonationResult;

    public function makeDonation(
        int $donorUserId,
        string $marketName,
        string $amount,
        string $fee,
        string $expectedAmount,
        int $tokenCreatorId
    ): void;
}
