<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceHistory;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;
use App\Exchange\Balance\Model\UpdateBalanceResult;

/**
 * Interface BalanceFetcherInterface
 *
 * @package App\Exchange\Balance
 */
interface BalanceFetcherInterface
{
    /**
     * @param int $userId
     * @param string $tokenName
     * @param string $amount
     * @param string $type
     * @param int|null $businessId
     * @throws BalanceException
     * @throws FetchException
     */
    public function update(
        int $userId,
        string $tokenName,
        string $amount,
        string $type,
        ?int $businessId = null
    ): UpdateBalanceResult;

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

    public function summary(string $tokenName): SummaryResult;

    /**
     * @param int $userId
     * @param array $tokenName
     * @return BalanceResultContainer
     */
    public function balance(int $userId, array $tokenName): BalanceResultContainer;

    /**
     * @return array{0: int, 1: string, 2: string}[]
     */
    public function topBalances(string $tradableName, int $limit, int $offset = 0, bool $withAvailable = false): array;
}
