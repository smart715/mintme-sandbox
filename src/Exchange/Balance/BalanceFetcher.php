<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

use App\Communications\JsonRpcInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Balance\Model\BalanceResultFactory;
use App\Exchange\Balance\Model\SummaryResult;
use App\Exchange\Config\Config;
use App\Utils\RandomNumberInterface;
use App\Wallet\Money\MoneyWrapperInterface;

/**
 * Class BalanceFetcher
 *
 * @package App\Exchange\Balance
 */
class BalanceFetcher implements BalanceFetcherInterface
{
    private const UPDATE_BALANCE_METHOD = 'balance.update';
    private const SUMMARY_METHOD = 'asset.summary';
    private const BALANCE_METHOD = 'balance.query';
    private const BALANCE_TOP_METHOD = 'balance.top';

    /** @var JsonRpcInterface */
    private JsonRpcInterface $jsonRpc;

    /** @var RandomNumberInterface */
    private RandomNumberInterface $random;

    /** @var MoneyWrapperInterface */
    private MoneyWrapperInterface $moneyWrapper;

    /** @var Config */
    private Config $config;

    /**
     * BalanceFetcher constructor.
     *
     * @param JsonRpcInterface $jsonRpc
     * @param RandomNumberInterface $randomNumber
     * @param MoneyWrapperInterface $moneyWrapper
     * @param Config $config
     */
    public function __construct(
        JsonRpcInterface $jsonRpc,
        RandomNumberInterface $randomNumber,
        MoneyWrapperInterface $moneyWrapper,
        Config $config
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->random = $randomNumber;
        $this->moneyWrapper = $moneyWrapper;
        $this->config = $config;
    }

    public function update(int $userId, string $tokenName, string $amount, string $type, ?int $businessId = null): void
    {
        try {
            $response = $this->jsonRpc->send(self::UPDATE_BALANCE_METHOD, [
                $userId + $this->config->getOffset(),
                $tokenName,
                $type,
                $businessId ?? $this->random->getNumber(),
                $amount,
                ['extra' => 1],
            ]);
        } catch (\Throwable $e) {
            throw $e;
        }

        if ($response->hasError()) {
            throw new BalanceException($response->getError()['message'] ?? 'unknown error');
        }
    }

    public function summary(string $tokenName): SummaryResult
    {
        $response = $this->jsonRpc->send(self::SUMMARY_METHOD, [
            $tokenName,
        ]);

        if ($response->hasError()) {
            throw new BalanceException($response->getError()['message'] ?? 'get error response');
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

    public function topBalances(string $tradableName, int $limit): array
    {
        $response = $this->jsonRpc->send(self::BALANCE_TOP_METHOD, [
            $tradableName,
            $limit,
        ]);

        if ($response->hasError()) {
            throw new BalanceException($response->getError()['message'] ?? 'get error response');
        }

        return $response->getResult();
    }

    public function balance(int $userId, array $tokenNames): BalanceResultContainer
    {
        if (!$tokenNames) {
            throw new BalanceException('Failed to get the balance. No token name', BalanceException::EMPTY);
        }

        $response = $this->jsonRpc->send(
            self::BALANCE_METHOD,
            array_merge([$userId + $this->config->getOffset()], $tokenNames)
        );

        if ($response->hasError()) {
            throw new BalanceException($response->getError()['message'] ?? 'get error response');
        }

        $result = $response->getResult();

        return (new BalanceResultFactory($result, $this->moneyWrapper))->create();
    }
}
