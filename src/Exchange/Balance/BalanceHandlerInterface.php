<?php

namespace App\Exchange\Balance;

use App\Entity\Token;
use App\Entity\User;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\SummaryResult;

interface BalanceHandlerInterface
{
    /**
     * @throws \Exception
     * @throws BalanceException
     */
    public function deposit(User $user, Token $token, int $amount): void;

    /**
     * @throws \Exception
     * @throws BalanceException
     */
    public function withdraw(User $user, Token $token, int $amount): void;

    public function summary(Token $token): SummaryResult;
}
