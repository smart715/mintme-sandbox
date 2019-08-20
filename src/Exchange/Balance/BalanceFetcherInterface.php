<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Communications\Exception\FetchException;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\SummaryResult;

interface BalanceFetcherInterface
{
    /**
     * @throws FetchException
     * @throws BalanceException
     */
    public function update(int $userId, string $tokenName, string $amount, string $type): void;

    public function summary(string $tokenName): SummaryResult;
    public function balance(int $userId, array $tokenName): BalanceResultContainer;
    public function topBalances(string $tradableName, int $limit): array;
}
