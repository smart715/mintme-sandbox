<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\User;

class UserAirdropEvent extends AirdropEvent implements UserEventInterface
{
    protected User $user;

    public function __construct(Airdrop $airdrop, User $user)
    {
        $this->user = $user;
        parent::__construct($airdrop);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
