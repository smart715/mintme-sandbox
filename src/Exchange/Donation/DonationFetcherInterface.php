<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Exchange\Donation\Model\CheckDonationRawResult;

interface DonationFetcherInterface
{
    public function checkDonation(
        string $marketName,
        string $amount,
        string $fee,
        int $tokenCreatorId
    ): CheckDonationRawResult;

    public function makeDonation(
        int $donorUserId,
        string $marketName,
        string $amount,
        string $fee,
        string $expectedAmount,
        int $tokenCreatorId
    ): void;
}
