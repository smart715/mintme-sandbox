<?php

namespace App\Manager;

use App\Entity\Profile;
use FOS\UserBundle\Model\UserInterface;

interface ProfileManagerInterface
{
    public function getProfile(UserInterface $user): ?Profile;
}
