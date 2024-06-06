<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\MarketEventInterface;

class MarketActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof MarketEventInterface) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        return new Activity($event->getType(), [
            'marketIconUrl' => $this->activityHelper->tradeIcon($event->getCrypto()->getSymbol()),
            'marketUrl' => $this->router->generate('token_show_trade', [
                'name' => $event->getToken()->getName(),
                'crypto' => $this->activityHelper->rebrand($event->getCrypto()->getSymbol()),
            ]),
            'market' =>
                $event->getToken()->getName() . "/" .$this->activityHelper->rebrand($event->getCrypto()->getSymbol()),
        ]);
    }
}
