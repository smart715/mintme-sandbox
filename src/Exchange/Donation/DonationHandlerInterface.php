<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Exchange\Market;

interface DonationHandlerInterface
{
    public function checkDonation(Market $market, string $amount, string $fee): string;
    public function makeDonation(Market $market, string $amount, string $fee, string $expectedAmount): void;
}
