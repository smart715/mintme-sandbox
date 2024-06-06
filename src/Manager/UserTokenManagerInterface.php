<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;
use Money\Money;

interface UserTokenManagerInterface
{
    /**
     * @return UserToken[]
     */
    public function findByUser(User $user): array;
    public function findByUserToken(User $user, Token $token): ?UserToken;
    public function updateRelation(User $user, Token $token, Money $balance, bool $isReferral = false): void;
    public function getUserOwnsCount(int $userId): int;
    /**
     * @return UserToken[]
     */
    public function getHoldersWithDiscord(Token $token): array;
}
