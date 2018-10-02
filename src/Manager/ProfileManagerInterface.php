<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\Token;

interface ProfileManagerInterface
{
    public function getProfileByPageUrl(String $pageUrl): ?Profile;
    public function findByEmail(string $email): ?Profile;
    public function generatePageUrl(Profile $profile): ?string;

    /**
     * @param mixed $user
     */
    public function getProfile($user): ?Profile;

    public function findByToken(Token $token): Profile;
}
