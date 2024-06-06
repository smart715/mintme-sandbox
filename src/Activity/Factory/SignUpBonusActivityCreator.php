<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Events\Activity\SignupBonusActivity;

class SignUpBonusActivityCreator extends AbstractActivityCreator
{
    public function create(ActivityEventInterface $event): Activity
    {
        if (!$event instanceof SignupBonusActivity) {
            throw new \InvalidArgumentException('Unsupported event');
        }

        $token = $event->getToken();

        return new Activity($event->getType(), [
            'signUpBonusUrl' => $this->router->generate('token_sign_up', ['name' => $token->getName()]),
            'tokenIconUrl' => $this->activityHelper->tokenIcon($token),
            'tokenUrl' => $this->router->generate('token_show_intro', ['name' => $token->getName()]),
            'token' => $this->activityHelper->truncate($token->getName(), 12),
        ]);
    }
}
