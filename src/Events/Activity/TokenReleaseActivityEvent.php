<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Activity\ActivityTypes;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;

/** @codeCoverageIgnore */
class TokenReleaseActivityEvent extends TokenEventActivity
{
    public const NAME = 'lock.in.token.activity';
    private LockIn $lockIn;

    public function __construct(LockIn $lockIn, Token $token)
    {
        $this->lockIn = $lockIn;

        parent::__construct($token, ActivityTypes::TOKEN_RELEASE_SET);
    }

    public function getLockIn(): LockIn
    {
        return $this->lockIn;
    }
}
