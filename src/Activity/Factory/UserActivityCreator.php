<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\UserEventInterface;

class UserActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof UserEventInterface) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        return new Activity($event->getType(), [
            'userIconUrl' => $this->activityHelper->profileIcon($event->getUser()),
            'userUrl' => $this->router->generate('profile-view', [
                'nickname' => $event->getUser()->getProfile()->getNickname(),
            ]),
            'user' => $this->activityHelper->truncate($event->getUser()->getProfile()->getNickname(), 12),
        ]);
    }
}
