<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Logger\UserActionLogger;
use FOS\UserBundle\Event\FilterUserResponseEvent;

class ChangePasswordListener
{
    /** @var UserActionLogger */
    private $userActionLogger;

    public function __construct(UserActionLogger $userActionLogger)
    {
        $this->userActionLogger = $userActionLogger;
    }


    public function onFosuserChangepasswordEditCompleted(FilterUserResponseEvent $event): void
    {
        $this->userActionLogger->info('Change password', ['email' => $event->getUser()->getEmail()]);
    }
}
