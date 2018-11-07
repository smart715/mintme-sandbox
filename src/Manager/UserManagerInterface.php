<?php

namespace App\Manager;

use App\Entity\User;
use FOS\UserBundle\Model\UserInterface;

interface UserManagerInterface
{
    public function createUserReferral(UserInterface $user, string $referralCode): ?User;
    public function getReferencesTotal(int $userId): ?int;
}
