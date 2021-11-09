<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropAction;
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
    public function tokenBlockAirDropBalance(Airdrop $activeAirdrop): void;
    public function createAction(string $action, ?string $actionData, Airdrop $airdrop): void;
    public function claimAirdropAction(AirdropAction $action, User $user): void;
    public function checkIfUserCompletedActions(Airdrop $airdrop, User $user): bool;
    public function claimAirdropsActionsFromSessionData(User $user): void;
}
