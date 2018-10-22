<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\Token;
use App\Entity\User;

interface ProfileManagerInterface
{
    public function getProfileByPageUrl(String $pageUrl): ?Profile;
    public function findByEmail(string $email): ?Profile;
    public function generatePageUrl(Profile $profile): ?string;
    public function findHash(User $user);
    public function validateUserApi(?string $token);

    /**
     * @param mixed $user
     */
    public function getProfile($user): ?Profile;
}
