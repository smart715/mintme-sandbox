<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Profile;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserNotificationEvent extends Event implements UserNotificationEventInterface
{
    public const NAME = 'user.notification';

    /** @var User */
    protected $user;

    /** @var String */
    protected $notificationType;

    protected array $extraData;

    public function __construct(User $user, String $notificationType, array $extraData = [])
    {
        $this->user = $user;
        $this->notificationType = $notificationType;
        $this->extraData = $extraData;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getNotificationType(): string
    {
        return $this->notificationType;
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }
}
