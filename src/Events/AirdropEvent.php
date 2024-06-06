<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use App\Events\Activity\TokenEventActivity;

/** @codeCoverageIgnore */
class AirdropEvent extends TokenEventActivity implements AirdropEventInterface
{
    protected Airdrop $airdrop;

    public function __construct(Airdrop $airdrop, int $type)
    {
        parent::__construct($airdrop->getToken(), $type);

        $this->airdrop = $airdrop;
    }

    public function getAirdrop(): Airdrop
    {
        return $this->airdrop;
    }

    public function getToken(): Token
    {
        return $this->airdrop->getToken();
    }
}
