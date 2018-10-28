<?php

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;

interface BalanceHandlerInterface
{
    /**
     * @throws FetchException
     * @throws BalanceException
     */
    public function deposit(User $user, Token $token, int $amount): void;

    /**
     * @throws FetchException
     * @throws BalanceException
     */
    public function withdraw(User $user, Token $token, int $amount): void;

    public function summary(Token $token): SummaryResult;
    public function balance(User $user, Token $token): BalanceResult;
    public function balances(User $user, array $tokens): BalanceResultContainer;
}
