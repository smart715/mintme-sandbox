<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

interface DonationFetcherInterface
{
    public function checkDonation(string $marketName, string $amount, string $fee): string;
    public function makeDonation(string $marketName, string $amount, string $fee, string $expectedAmount): void;
}
