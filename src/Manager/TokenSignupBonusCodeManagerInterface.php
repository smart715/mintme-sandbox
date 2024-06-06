<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\TokenSignupBonusCode;
use App\Entity\User;
use Money\Money;

interface TokenSignupBonusCodeManagerInterface
{
    public function createTokenSignupBonusLink(
        Token $token,
        Money $bonusAmount,
        int $participants
    ): TokenSignupBonusCode;

    public function deleteTokenSignupBonusLink(Token $token): void;

    public function withdrawTokenSignupBonus(Token $token, User $user, Money $amount): void;

    public function claimTokenSignupBonus(Token $token, User $user, Money $amount): void;
}
