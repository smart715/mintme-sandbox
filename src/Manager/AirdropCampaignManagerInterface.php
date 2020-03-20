<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use Money\Money;

interface AirdropCampaignManagerInterface
{
    public function createAirdrop(
        Token $token,
        string $amount,
        int $participants,
        ?\DateTimeImmutable $endDate = null
    ): Airdrop;
    public function deleteAirdrop(Airdrop $airdrop): void;
}
