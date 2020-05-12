<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\User;
use App\Entity\UserLoginInfo;

interface NewDeviceDetectedInterface
{
    public function getUser(): User;
    public function getUserDeviceLoginInfo(): UserLoginInfo;
}
