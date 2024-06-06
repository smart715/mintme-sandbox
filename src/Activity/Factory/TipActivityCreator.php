<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\Activity\TipTokenEventActivity;

class TipActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof TipTokenEventActivity) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        $token = $event->getToken();
        $tip = $event->getCommentTip();
        $user = $tip->getCommentAuthor();

        return new Activity($event->getType(), [
            'tokenIconUrl' => $this->activityHelper->tokenIcon($token),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $token->getName()]),
            'token' => $this->activityHelper->truncate($token->getName(), 12),
            'userIconUrl' => $this->activityHelper->profileIcon($user),
            'userUrl' => $this->router->generate('profile-view', [
                'nickname' => $user->getProfile()->getNickname(),
            ]),
            'user' => $this->activityHelper->truncate($user->getProfile()->getNickname(), 12),
            'amount' => $this->moneyWrapper->format($tip->getAmount(), false),
        ]);
    }
}
