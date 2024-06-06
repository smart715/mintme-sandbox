<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\UserToken;

interface TokensUserOwnsViewFactoryInterface
{
    /**
     * @param UserToken[] $userTokens
     * @return TokensUserOwnsView[]
     */
    public function create(array $userTokens, bool $isHideZeroBalanceTokens = false): array;
}
