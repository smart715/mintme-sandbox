<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use Symfony\Contracts\EventDispatcher\Event;

class AirdropEvent extends Event implements AirdropEventInterface, TokenEventInterface
{
    protected Airdrop $airdrop;

    public function __construct(Airdrop $airdrop)
    {
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
