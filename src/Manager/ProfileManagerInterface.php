<?php

namespace App\Manager;

use App\Entity\Profile;
use FOS\UserBundle\Model\UserInterface;

interface ProfileManagerInterface
{
    public function createProfile(UserInterface $user): Profile;
    public function createProfileReferral(UserInterface $user, string $referralCode): Profile;
    public function getProfile(UserInterface $user): ?Profile;
    public function lockChangePeriod(Profile $profile): void;
}
