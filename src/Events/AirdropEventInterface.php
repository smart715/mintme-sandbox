<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\AirdropCampaign\Airdrop;

interface AirdropEventInterface
{
    public function getAirdrop(): Airdrop;
}
