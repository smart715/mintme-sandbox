<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\CommentEvent;

class CommentLikeActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof CommentEvent) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        return new Activity($event->getType(), [
            'commentUrl' => $this->router->generate(
                'show_post',
                ['id' => $event->getComment()->getPost()->getId()]
            ) . '#comment-' . $event->getComment()->getId(),
            'comment' => $this->activityHelper->truncate($event->getComment()->getContent(), 32),
            'userIconUrl' => $this->activityHelper->profileIcon($event->getUser()),
            'userUrl' => $this->router->generate('profile-view', [
                'nickname' => $event->getUser()->getProfile()->getNickname(),
            ]),
            'user' => $this->activityHelper->truncate($event->getUser()->getProfile()->getNickname(), 12),
        ]);
    }
}
