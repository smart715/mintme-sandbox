<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Entity\Token\Token;
use App\Events\Activity\ActivityEventInterface;
use App\Events\UserAirdropEvent;

class AirdropClaimedActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof UserAirdropEvent) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        return new Activity($event->getType(), [
            'tokenIconUrl' => $this->activityHelper->tokenIcon($event->getToken()),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $event->getToken()->getName()]),
            'token' => $this->activityHelper->truncate($event->getToken()->getName(), 12),
            'amount' => $this->moneyWrapper->format($event->getAirdrop()->getReward(), false),
            'referralAmount' => $this->moneyWrapper->format($event->getAirdrop()->getReward()->divide(2), false),
            'symbol' => 'token(s)',
            'userIconUrl' => $this->activityHelper->profileIcon($event->getUser()),
            'userUrl' => $this->router->generate('profile-view', [
                'nickname' => $event->getUser()->getProfile()->getNickname(),
            ]),
            'user' => $this->activityHelper->truncate($event->getUser()->getProfile()->getNickname(), 12),
        ]);
    }
}
