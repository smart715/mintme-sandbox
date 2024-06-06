<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\DonationEvent;

class DonationActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof DonationEvent) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        return new Activity($event->getType(), [
            'tokenIconUrl' => $this->activityHelper->tokenIcon($event->getToken()),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $event->getToken()->getName()]),
            'token' => $this->activityHelper->truncate($event->getToken()->getName(), 12),
            'fullTokenName' => $event->getToken()->getName(),
            'amount' => $this->moneyWrapper->format($event->getDonation()->getAmount(), false),
            'symbol' => $this->activityHelper->rebrand($event->getDonation()->getCurrency()),
            'tradeIconUrl' => $this->activityHelper->tradeIcon(
                $event->getDonation()->getCurrency()
            ),
            'id' => $event->getDonation()->getId(),
        ]);
    }
}
