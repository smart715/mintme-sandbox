<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\Activity\BonusEventActivity;

class BonusActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof BonusEventActivity) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        $user = $event->getUser();
        $bonus = $event->getBonus();
        $token = $event->getToken();

        $context = [
            'userIconUrl' => $this->activityHelper->profileIcon($user),
            'userUrl' => $this->router->generate('profile-view', [
                'nickname' => $user->getProfile()->getNickname(),
            ]),
            'user' => $this->activityHelper->truncate($user->getProfile()->getNickname(), 12),
            'tokenIconUrl' => $this->activityHelper->tokenIcon($token),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $token->getName()]),
            'token' => $this->activityHelper->truncate($token->getName(), 12),
            'amount' => $this->moneyWrapper->format($bonus->getQuantity(), false),
        ];

        return new Activity($event->getType(), $context);
    }
}
