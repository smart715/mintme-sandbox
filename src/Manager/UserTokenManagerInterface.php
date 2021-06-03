<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;
use Money\Money;

interface UserTokenManagerInterface
{
    public function findByUserToken(User $user, Token $token): ?UserToken;
    public function updateRelation(User $user, Token $token, Money $balance): void;
}
