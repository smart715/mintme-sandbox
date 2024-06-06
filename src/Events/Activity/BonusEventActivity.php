<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Activity\ActivityTypes;
use App\Entity\Bonus;
use App\Entity\Token\Token;

/** @codeCoverageIgnore */
class BonusEventActivity extends UserTokenEventActivity
{
    public const NAME = 'bonus.activity';

    private Bonus $bonus;

    public function __construct(Bonus $bonus, Token $token)
    {
        $this->bonus = $bonus;

        parent::__construct($bonus->getUser(), $token, ActivityTypes::SIGN_UP_CAMPAIGN);
    }

    public function getBonus(): Bonus
    {
        return $this->bonus;
    }
}
