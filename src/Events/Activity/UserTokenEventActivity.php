<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\TokenUserEventInterface;

/** @codeCoverageIgnore */
class UserTokenEventActivity extends TokenEventActivity implements TokenUserEventInterface
{
    public const NAME = 'user.token.activity';
    private User $user;

    public function __construct(User $user, Token $token, int $type)
    {
        $this->user = $user;

        parent::__construct($token, $type);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
