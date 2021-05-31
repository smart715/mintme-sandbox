<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use Money\Money;

interface UserTokenManagerInterface
{
    public function updateRelation(User $user, Token $token, Money $balance): void;
}
