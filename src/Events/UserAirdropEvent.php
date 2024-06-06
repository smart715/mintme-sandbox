<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\User;
use App\Events\Activity\ActivityEventInterface;

/** @codeCoverageIgnore */
class UserAirdropEvent extends AirdropEvent implements TokenUserEventInterface
{
    protected User $user;
    protected int $type;

    public function __construct(Airdrop $airdrop, User $user, int $type)
    {
        $this->user = $user;
        parent::__construct($airdrop, $type);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
