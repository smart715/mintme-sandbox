<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\Activity\RewardEventActivity;

class RewardActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof RewardEventActivity) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        $context = [
            'tokenIconUrl' => $this->activityHelper->tokenIcon($event->getReward()->getToken()),
            'tokenUrl' => $this->router->generate(
                'token_show_intro',
                ['name' => $event->getReward()->getToken()->getName()]
            ),
            'token' => $this->activityHelper->truncate($event->getReward()->getToken()->getName(), 12),
            'rewardTitle' => $this->activityHelper->truncate($event->getReward()->getTitle(), 12),
            'rewardUrl' => $this->router->generate(
                'token_show_intro',
                [
                    'name' => $event->getReward()->getToken()->getName(),
                    'modal' => 'reward-finalize',
                    'slug' => $event->getReward()->getSlug(),
                ]
            ),
            'amount' => $this->moneyWrapper->format($event->getReward()->getPrice(), false),
        ];

        if ($event->getRewardMember()) {
            $context += [
                'memberIconUrl' => $this->activityHelper->profileIcon($event->getRewardMember()->getUser()),
                'memberUrl' => $this->router->generate('profile-view', [
                    'nickname' => $event->getRewardMember()->getUser()->getProfile()->getNickname(),
                ]),
                'member' => $this->activityHelper->truncate(
                    $event->getRewardMember()->getUser()->getProfile()->getNickname(),
                    12
                ),
            ];
        }

        return new Activity($event->getType(), $context);
    }
}
