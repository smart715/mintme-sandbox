<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserToken;
use Money\Money;

interface UserTokenManagerInterface
{
    public function findByUserToken(User $user, TradebleInterface $token): ?UserToken;
    public function updateRelation(User $user, TradebleInterface $token, Money $balance): void;
}
