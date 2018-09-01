<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\Token;

interface ProfileManagerInterface
{
    /**
     * @param mixed $user
     */
    public function getProfile($user): ?Profile;

    public function findByToken(Token $token): Profile;

    public function lockChangePeriod(Profile $profile): void;
}
