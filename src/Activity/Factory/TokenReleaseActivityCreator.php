<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\Activity\TokenReleaseActivityEvent;

class TokenReleaseActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof TokenReleaseActivityEvent) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        $token = $event->getToken();

        $context = [
            'tokenIconUrl' => $this->activityHelper->tokenIcon($token),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $token->getName()]),
            'token' => $this->activityHelper->truncate($token->getName(), 12),
            'amount' => $this->moneyWrapper->format($event->getLockIn()->getAmountToRelease(), false),
            'duration' => $event->getLockIn()->getReleasePeriod(),
        ];

        return new Activity($event->getType(), $context);
    }
}
