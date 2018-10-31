<?php

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;

interface TokenManagerInterface
{
    public function findByName(string $name): ?Token;

    public function getOwnToken(): ?Token;

    public function findAll(): array;

    public function getRealBalance(Token $token, BalanceResult $balanceResult): BalanceResult;
}
