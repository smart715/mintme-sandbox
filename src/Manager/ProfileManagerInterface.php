<?php

namespace App\Manager;

use App\Entity\Profile;

interface ProfileManagerInterface
{
    /**
     * @param mixed $user
     */
    public function getProfile($user): ?Profile;

    public function lockChangePeriod(Profile $profile): void;
}
