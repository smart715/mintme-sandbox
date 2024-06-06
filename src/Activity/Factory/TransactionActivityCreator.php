<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Entity\Token\Token;
use App\Events\Activity\ActivityEventInterface;
use App\Events\TransactionCompletedEventInterface;
use App\Utils\Symbols;

class TransactionActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof TransactionCompletedEventInterface) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        return new Activity($event->getType(), [
            'tokenIconUrl' => $this->activityHelper->tradeIcon(
                $event->getTradable()->getSymbol(),
                $event->getTradable()
            ),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $event->getTradable()->getName()]),
            'token' => $this->activityHelper->truncate($event->getTradable()->getName(), 12),
            'userIconUrl' => $this->activityHelper->profileIcon($event->getUser()),
            'userUrl' => $this->router->generate('profile-view', [
                'nickname' => $event->getUser()->getProfile()->getNickname(),
            ]),
            'user' => $this->activityHelper->truncate($event->getUser()->getProfile()->getNickname(), 12),
            'amount' => $this->activityHelper->getLastPriceWorthInMintMe($event),
            'symbol' => $this->activityHelper->rebrand($event->getTradable()->getSymbol()),
            'mintmeIconUrl' => $this->activityHelper->tradeIcon(Symbols::MINTME),
        ]);
    }
}
