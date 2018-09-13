<?php

namespace App\Manager;

use App\Entity\Profile;
use FOS\UserBundle\Model\UserInterface;

interface ProfileManagerInterface
{
    public function getProfile(UserInterface $user): ?Profile;
    public function getProfileByPageUrl(String $pageUrl): ?Profile;
    public function findByEmail(string $email): ?Profile;
    public function generatePageUrl(Profile $profile): ?string;
}
