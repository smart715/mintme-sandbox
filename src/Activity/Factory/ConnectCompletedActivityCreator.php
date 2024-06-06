<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\ConnectCompletedEvent;

class ConnectCompletedActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof ConnectCompletedEvent) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        return new Activity($event->getType(), [
            'tokenIconUrl' => $this->activityHelper->tokenIcon($event->getToken()),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $event->getToken()->getName()]),
            'token' => $this->activityHelper->truncate($event->getToken()->getName(), 12),
            'marketIconUrl' => $this->activityHelper->tradeIcon($event->getTokenDeploy()->getCrypto()->getSymbol()),
            'blockchain' => $this->activityHelper->rebrandBlockchain(
                $event->getTokenDeploy()->getCrypto()->getSymbol()
            ),
        ]);
    }
}
