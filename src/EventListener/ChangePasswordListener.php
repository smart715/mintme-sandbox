<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Logger\UserActionLogger;
use FOS\UserBundle\Event\FilterUserResponseEvent;

class ChangePasswordListener
{
    public function __construct(UserActionLogger $userActionLogger)
    {
        $this->userActionLogger = $userActionLogger;
    }

    /** @var UserActionLogger */
    private $userActionLogger;


    public function onFosuserChangepasswordEditSuccess(): void
    {
        $this->userActionLogger->info('Change password');
    }
}
