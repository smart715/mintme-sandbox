<?php

namespace App\Manager;

use App\Entity\User;

interface UserReferralManagerInterface
{
    public function createUserReferral(int $user, ?string $referralCode): ?User;
    public function getReferencesTotal(int $userId): ?int;
}
