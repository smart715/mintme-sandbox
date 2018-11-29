<?php

namespace App\Exchange\Balance;

use App\Communications\JsonRpcInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\BalanceResultFactory;
use App\Exchange\Balance\Model\SummaryResult;
use App\Utils\RandomNumberInterface;
use App\Wallet\Money\MoneyWrapperInterface;

class BalanceFetcher implements BalanceFetcherInterface
{
    private const UPDATE_BALANCE_METHOD = 'balance.update';
    private const SUMMARY_METHOD = 'asset.summary';
    private const BALANCE_METHOD = 'balance.query';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var RandomNumberInterface */
    private $random;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        JsonRpcInterface $jsonRpc,
        RandomNumberInterface $randomNumber,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->random = $randomNumber;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function update(int $userId, string $tokenName, string $amount, string $type): void
    {
        $responce = $this->jsonRpc->send(self::UPDATE_BALANCE_METHOD, [
            $userId,
            $tokenName,
            $type,
            $this->random->getNumber(),
            $amount,
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

    public function balance(int $userId, array $tokenNames): BalanceResultContainer
    {
        if (!$tokenNames) {
            return BalanceResultContainer::fail();
        }

        try {
            $response = $this->jsonRpc->send(
                self::BALANCE_METHOD,
                array_merge([ $userId ], $tokenNames)
            );
        } catch (\Throwable $exception) {
            return BalanceResultContainer::fail();
        }

        if ($response->hasError()) {
            return BalanceResultContainer::fail();
        }

        $result = $response->getResult();

        return (new BalanceResultFactory($result, $this->moneyWrapper))->create();
    }
}