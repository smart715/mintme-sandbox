<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Entity\User;
use App\Exchange\Donation\Model\CheckDonationResult;
use App\Exchange\Market;
use Money\Money;

interface DonationCheckerInterface
{
    public function checkOneWayDonation(Market $market, Money $donationAmount, User $tokenCreator): CheckDonationResult;
    public function checkTwoWayDonation(Market $market, Money $donationAmount, User $tokenCreator): CheckDonationResult;
}
