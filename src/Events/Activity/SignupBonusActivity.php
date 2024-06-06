<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Entity\TokenSignupBonusCode;

/** @codeCoverageIgnore */
class SignupBonusActivity extends TokenEventActivity
{
    public const NAME = 'sign.up.bonus.activity';
    private TokenSignupBonusCode $bonus;

    public function __construct(TokenSignupBonusCode $tokenSignupBonusCode, int $type)
    {
        $this->bonus = $tokenSignupBonusCode;

        parent::__construct($tokenSignupBonusCode->getToken(), $type);
    }

    public function getTokenSignupBonusCode(): TokenSignupBonusCode
    {
        return $this->bonus;
    }
}
