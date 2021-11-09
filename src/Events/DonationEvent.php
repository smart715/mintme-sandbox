<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class DonationEvent extends Event implements TokenUserEventInterface, DonationEventInterface
{
    protected Donation $donation;

    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
    }

    public function getDonation(): Donation
    {
        return $this->donation;
    }

    public function getToken(): Token
    {
        /** @var Token $token */
        $token = $this->donation->getToken();

        return $token;
    }

    public function getUser(): User
    {
        return $this->donation->getDonor();
    }
}
