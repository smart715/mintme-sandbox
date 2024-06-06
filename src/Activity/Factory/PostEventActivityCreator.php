<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\PostEvent;

class PostEventActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof PostEvent) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        $user = $event->getUser();
        $post = $event->getPost();
        $token = $event->getToken();

        $context = [
            'postUrl' => $this->router->generate('token_show_post', [
                'name' => $token->getName(),
                'slug' => $post->getSlug(),
            ]),
            'post' => $this->activityHelper->truncate($post->getTitle(), 12),
            'userIconUrl' => $this->activityHelper->profileIcon($user),
            'userUrl' => $this->router->generate('profile-view', [
                'nickname' => $user->getProfile()->getNickname(),
            ]),
            'user' => $this->activityHelper->truncate($user->getProfile()->getNickname(), 12),
            'tokenIconUrl' => $this->activityHelper->tokenIcon($token),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $token->getName()]),
            'token' => $this->activityHelper->truncate($token->getName(), 12),
        ];

        if (!$post->getShareReward()->isZero()) {
            $context['amount'] = $this->moneyWrapper->format($post->getShareReward(), false);
        }

        return new Activity($event->getType(), $context);
    }
}
