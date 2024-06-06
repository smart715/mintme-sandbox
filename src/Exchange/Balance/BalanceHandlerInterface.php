<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\TraderBalanceView;
use App\Exchange\Balance\Factory\UpdateBalanceView;
use App\Exchange\Balance\Model\BalanceHistory;
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
     * Start the balance update process
     * Keeps track of the current state of the balance update process in case of failure
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Rollback the balance updates in case of failure
     * It would revert the changes made by the balance update process by re-updating the successfully updated balances
     *
     * @return void
     */
    public function rollback(): void;

    /**
     * @param User $user
     * @param TradableInterface $tradable
     * @param Money $amount
     * @param int|null $businessId
     * @throws FetchException
     * @throws BalanceException
     */
    public function deposit(User $user, TradableInterface $tradable, Money $amount, ?int $businessId = null): void;

    /**
     * @param User $user
     * @param TradableInterface $tradable
     * @param Money $amount
     * @param string $bonusType
     * @throws FetchException
     */
    public function depositBonus(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $bonusType
    ): void;

    /**
     * @param User $user
     * @param TradableInterface $tradable
     * @param Money $amount
     * @param int|null $businessId
     * @throws FetchException
     * @throws BalanceException
     */
    public function withdraw(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        ?int $businessId = null
    ): UpdateBalanceView;

    /**
     * @param User $user
     * @param TradableInterface $tradable
     * @param Money $amount
     * @param string $bonusType
     * @throws FetchException
     */
    public function withdrawBonus(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $bonusType
    ): UpdateBalanceView;

    /**
     * Returns the balance history for the given user in a given time range.
     *
     * @param int $userId
     * @param string $tokenName
     * @param string $type
     * @param int $startTime
     * @param int $endTime
     * @param int $offset
     * @param int $limit
     * @return BalanceHistory
     */
    public function history(
        int $userId,
        string $tokenName,
        string $type,
        int $startTime = 0,
        int $endTime = 0,
        int $offset = 0,
        int $limit = 50
    ): BalanceHistory;

    public function summary(Token $token): SummaryResult;

    public function balance(User $user, TradableInterface $tradable): BalanceResult;

    /**
     * @param User $user
     * @param array $cryptosValues
     * @return Money[]
     */
    public function getReferralBalances(User $user, array $cryptosValues): array;

    public function exchangeBalance(User $user, Token $token, bool $withBonus = false): Money;

    /**
     * @param User $user
     * @param TradableInterface[] $tradables
     * @return BalanceResultContainer
     */
    public function balances(User $user, array $tradables): BalanceResultContainer;

    /**
     * @param User $user
     * @param TradableInterface[] $tradables
     * @return BalanceResult[]
     */
    public function indexedBalances(User $user, array $tradables): array;

    public function isNotExchanged(Token $token, int $amount): bool;

    /**
     * @param  TradableInterface $tradable
     * @param  int $limit number to limit the return count.
     * @param  int $extend number of rows that we need from viabtc (offset param for viabtc).
     * @param  int $incrementer number to increment extend if number of rows became lower than limit after filtering.
     * @param  int $max number to limit the recursion calls.
     * @return TraderBalanceView[]
     */
    public function topHolders(
        TradableInterface $tradable,
        int $limit,
        int $extend = 15,
        int $incrementer = 5,
        int $max = 40
    ): array;

    public function update(
        User $user,
        TradableInterface $tradable,
        Money $amount,
        string $type,
        ?int $businessId = null
    ): UpdateBalanceView;

    public function updateUserTokenRelation(User $user, TradableInterface $tradable, bool $isReferral = false): void;

    public function isTransactionStarted(): bool;

    public function isServiceAvailable(): bool;
}
