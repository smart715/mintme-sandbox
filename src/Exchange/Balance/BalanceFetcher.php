<?php

namespace App\Exchange\Balance;

use App\Communications\JsonRpcInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\SummaryResult;
use App\Utils\RandomNumberInterface;

class BalanceFetcher implements BalanceFetcherInterface
{
    private const UPDATE_BALANCE_METHOD = 'balance.update';
    private const SUMMARY_METHOD = 'asset.summary';
    private const BALANCE_METHOD = 'balance.query';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var RandomNumberInterface */
    private $random;

    public function __construct(
        JsonRpcInterface $jsonRpc,
        RandomNumberInterface $randomNumber
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->random = $randomNumber;
    }

    public function update(int $userId, string $tokenName, int $amount, string $type): void
    {
        $responce = $this->jsonRpc->send(self::UPDATE_BALANCE_METHOD, [
            $userId,
            $tokenName,
            $type,
            $this->random->getNumber(),
            (string)$amount,
            [ 'extra' => 1 ],
        ]);

        if ($responce->hasError()) {
            throw new BalanceException();
        }
    }

    public function summary(string $tokenName): SummaryResult
    {
        try {
            $response = $this->jsonRpc->send(self::SUMMARY_METHOD, [
                $tokenName,
            ]);
        } catch (\Throwable $exception) {
            return SummaryResult::fail();
        }

        if ($response->hasError()) {
            return SummaryResult::fail();
        }

        $result = $response->getResult();

        return SummaryResult::success(
            $result['name'],
            (int)$result['total_balance'],
            (int)$result['available_balance'],
            $result['available_count'],
            (int)$result['freeze_balance'],
            $result['freeze_count']
        );
    }

    public function balance(int $userId, string $tokenName): BalanceResult
    {
        try {
            $response = $this->jsonRpc->send(self::BALANCE_METHOD, [
                $userId,
                $tokenName,
            ]);
        } catch (\Throwable $exception) {
            return BalanceResult::fail();
        }

        if ($response->hasError()) {
            return BalanceResult::fail();
        }

        $result = $response->getResult();

        return BalanceResult::success(
            (float)$result[$tokenName]['available'],
            (float)$result[$tokenName]['freeze']
        );
    }
}
