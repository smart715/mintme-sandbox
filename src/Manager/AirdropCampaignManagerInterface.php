<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use App\Entity\User;

interface AirdropCampaignManagerInterface
{
    public function createAirdrop(
        Token $token,
        string $amount,
        int $participants,
        ?\DateTimeImmutable $endDate = null
    ): Airdrop;
    public function deleteAirdrop(Airdrop $airdrop): void;
    public function showAirdropCampaign(User $user, Token $token): bool;
}
