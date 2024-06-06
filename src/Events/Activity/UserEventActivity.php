<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Entity\User;
use App\Events\UserEventInterface;

/** @codeCoverageIgnore */
class UserEventActivity implements UserEventInterface, ActivityEventInterface
{
    public const NAME = 'user.activity';

    private User $user;
    private int $type;

    public function __construct(User $user, int $type)
    {
        $this->user = $user;
        $this->type = $type;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
