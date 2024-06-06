<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\UserChangeEvents;
use App\Events\UserChangeLockEvent;
use App\Utils\LockFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WithdrawLockSuscriber implements EventSubscriberInterface
{
    private LockFactory $lockFactory;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserChangeEvents::PASSWORD_UPDATED => 'onLockWithdraw',
            UserChangeEvents::PHONE_UPDATED => 'onLockWithdraw',
            UserChangeEvents::EMAIL_UPDATED => 'onLockWithdraw',
            UserChangeEvents::TWO_FACTOR_DISABLED => 'onLockWithdraw',
        ];
    }

    public function onLockWithdraw(UserChangeLockEvent $event): void
    {
        $lockWithdraw = $this->lockFactory->createLock(
            $event->getLockKey(),
            $event->getLockPeriod(),
            false
        );

        $lockWithdraw->acquire();
    }
}
