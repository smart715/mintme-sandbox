<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\TraderBalanceView;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;
use Money\Money;

interface BalanceHandlerInterface
{
    /**
     * @throws FetchException
     * @throws BalanceException
     */
    public function deposit(User $user, Token $token, Money $amount): void;

    /**
     * @throws FetchException
     * @throws BalanceException
     */
    public function withdraw(User $user, Token $token, Money $amount): void;

    public function summary(Token $token): SummaryResult;
    public function balance(User $user, Token $token): BalanceResult;
    public function balances(User $user, array $tokens): BalanceResultContainer;
    public function isNotExchanged(Token $token, int $amount): bool;

    /**
     * @param  TradebleInterface $tradable
     * @param  int $limit
     * @param  int $extend
     * @param  int $incrementer
     * @param  int $max
     * @return TraderBalanceView[]
     */
    public function topTraders(
        TradebleInterface $tradable,
        int $limit,
        int $extend = 15,
        int $incrementer = 5,
        int $max = 40
    ): array;
}
