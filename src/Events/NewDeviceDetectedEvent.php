<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\User;
use App\Entity\UserLoginInfo;
use Symfony\Contracts\EventDispatcher\Event;

class NewDeviceDetectedEvent extends Event implements NewDeviceDetectedEventInterface
{
    public const NAME = 'newdevice.detected';

    /** @var User */
    protected $user;

    /** @var UserLoginInfo */
    protected $userDeviceLoginInfo;

    public function __construct(User $user, UserLoginInfo $userDeviceLoginInfo)
    {
        $this->userDeviceLoginInfo = $userDeviceLoginInfo;
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserDeviceLoginInfo(): UserLoginInfo
    {
        return $this->userDeviceLoginInfo;
    }
}
