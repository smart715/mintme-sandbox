<?php declare(strict_types = 1);

namespace App\Manager;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

interface UserLoginInfoInterface
{
    public function updateUserDeviceLoginInfo(InteractiveLoginEvent $event): void;
}
