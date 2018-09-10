<?php

namespace App\Manager;

use App\Entity\Profile;
use FOS\UserBundle\Model\UserInterface;

interface ProfileManagerInterface
{
    public function getProfile(UserInterface $user): ?Profile;
    public function findByEmail(string $email): ?Profile;
    public function lockChangePeriod(Profile $profile): void;
}
