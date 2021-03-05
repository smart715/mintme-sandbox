<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Donation;

interface DonationEventInterface
{
    public function getDonation(): Donation;
}
