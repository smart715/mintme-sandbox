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
use App\Exchange\Order;
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
     * @param Token $token
     * @param int $amount
     * @param Order[] $ownerPendingOrders
     * @return Money
     */
    public function soldOnMarket(Token $token, int $amount, array $ownerPendingOrders): Money;

    /**
     * @param  TradebleInterface $tradable
     * @param  int $limit number to limit the return count.
     * @param  int $extend number of rows that we need from viabtc (offset param for viabtc).
     * @param  int $incrementer number to increment extend if number of rows became lower than limit after filtering.
     * @param  int $max number to limit the recursion calls.
     * @return TraderBalanceView[]
     */
    public function topHolders(
        TradebleInterface $tradable,
        int $limit,
        int $extend = 15,
        int $incrementer = 5,
        int $max = 40
    ): array;

    public function update(User $user, Token $token, Money $amount, string $type): void;
}
