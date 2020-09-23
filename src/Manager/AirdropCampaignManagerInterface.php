<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use App\Entity\User;
use Money\Money;

interface AirdropCampaignManagerInterface
{
    public function createAirdrop(
        Token $token,
        Money $amount,
        int $participants,
        ?\DateTimeImmutable $endDate = null
    ): Airdrop;
    public function deleteAirdrop(Airdrop $airdrop): void;
    public function checkIfUserClaimed(?User $user, Token $token): bool;
    public function claimAirdropCampaign(User $user, Token $token): void;
    public function updateOutdatedAirdrops(): int;
}
