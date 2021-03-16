<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropReferralCode;
use App\Entity\User;

interface AirdropReferralCodeManagerInterface
{
    public function encode(AirdropReferralCode $arc): string;

    public function encodeHash(int $id): string;

    public function decodeHash(string $hash): int;

    public function decode(string $hash): ?AirdropReferralCode;

    public function getByAirdropAndUser(Airdrop $airdrop, User $user): ?AirdropReferralCode;

    public function getById(int $id): ?AirdropReferralCode;

    public function create(Airdrop $airdrop, User $user): AirdropReferralCode;
}
