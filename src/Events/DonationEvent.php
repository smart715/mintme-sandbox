<?php declare(strict_types = 1);

namespace App\Events;

use App\Activity\ActivityTypes;
use App\Entity\Donation;
use App\Events\Activity\UserTokenEventActivity;

/** @codeCoverageIgnore */
class DonationEvent extends UserTokenEventActivity implements DonationEventInterface
{
    protected Donation $donation;

    public function __construct(Donation $donation)
    {
        $this->donation = $donation;

        parent::__construct($donation->getDonor(), $donation->getToken(), ActivityTypes::DONATION);
    }

    public function getDonation(): Donation
    {
        return $this->donation;
    }
}
