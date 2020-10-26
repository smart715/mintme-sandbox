<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;

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
    public function update(int $userId, string $tokenName, string $amount, string $type, ?int $businessId = null): void;

    public function summary(string $tokenName): SummaryResult;

    /**
     * @param int $userId
     * @param array $tokenName
     * @return BalanceResultContainer
     */
    public function balance(int $userId, array $tokenName): BalanceResultContainer;

    /**
     * @param string $tradableName
     * @param int $limit
     * @return array
     */
    public function topBalances(string $tradableName, int $limit): array;
}
