<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\Activity\UserVotingEventActivity;
use App\Events\Activity\VotingEventActivity;

class VotingActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof VotingEventActivity) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        $token = $event->getToken();
        $voting = $event->getVoting();
        $context = [
            'propositionUrl' => $this->router->generate('show_voting', ['slug' => $voting->getSlug()]),
            'proposition' => $this->activityHelper->truncate($voting->getTitle(), 32),
            'tokenIconUrl' => $this->activityHelper->tokenIcon($token),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $token->getName()]),
            'token' => $this->activityHelper->truncate($token->getName(), 12),
        ];

        if ($event instanceof UserVotingEventActivity) {
            $user = $event->getUser();
            $extraContext = [
                'userIconUrl' => $this->activityHelper->profileIcon($user),
                'userUrl' => $this->router->generate('profile-view', [
                    'nickname' => $user->getProfile()->getNickname(),
                ]),
                'user' => $this->activityHelper->truncate($user->getProfile()->getNickname(), 12),
            ];
            $context += $extraContext;
        }

        return new Activity($event->getType(), $context);
    }
}
