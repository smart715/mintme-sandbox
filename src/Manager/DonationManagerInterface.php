<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Donation;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Utils\Symbols;
use Money\Money;

interface DonationManagerInterface
{
    /**
     * @param User $user
     * @return Donation[]
     */
    public function getUserRelated(User $user, int $offset, int $limit): array;

    public function getDirectBuyVolume(Token $token): Money;

    public function getDonationReferralRewards(User $user): Money;

    public function getTotalRewardsGiven(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
