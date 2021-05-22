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

/**
 * Interface BalanceHandlerInterface
 *
 * @package App\Exchange\Balance
 */
interface BalanceHandlerInterface
{
    /**
     * @param User $user
     * @param Token $token
     * @param Money $amount
     * @param int|null $businessId
     * @throws FetchException
     * @throws BalanceException
     */
    public function deposit(User $user, Token $token, Money $amount, ?int $businessId = null): void;

    /**
     * @param User $user
     * @param Token $token
     * @param Money $amount
     * @param int|null $businessId
     * @throws FetchException
     * @throws BalanceException
     */
    public function withdraw(User $user, Token $token, Money $amount, ?int $businessId = null): void;

    public function summary(Token $token): SummaryResult;

    public function balance(User $user, TradebleInterface $tradable): BalanceResult;

    public function exchangeBalance(User $user, Token $token): Money;

    /**
     * @param User $user
     * @param array $tokens
     * @return BalanceResultContainer
     */
    public function balances(User $user, array $tradables): BalanceResultContainer;

    public function isNotExchanged(Token $token, int $amount): bool;

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

    public function update(User $user, Token $token, Money $amount, string $type, ?int $businessId = null): void;

    public function updateUserTokenRelation(User $user, Token $token): void;
}
