<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

interface DonationHandlerInterface
{
    public function checkDonation(string $market, string $amount, string $fee): string;
    public function makeDonation(string $market, string $amount, string $fee, string $expectedAmount): void;
}
