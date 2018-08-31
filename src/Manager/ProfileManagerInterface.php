<?php

namespace App\Manager;

use App\Entity\Profile;
use FOS\UserBundle\Model\UserInterface;

interface ProfileManagerInterface
{
    public function getProfile(UserInterface $user): ?Profile;
    public function getProfileByPageUrl(String $pageUrl): ?Profile;
    public function lockChangePeriod(Profile $profile): void;
    public function generatePageUrl(Profile $profile): ?string;
}
