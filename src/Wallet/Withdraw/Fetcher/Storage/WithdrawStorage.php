<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Storage;

use App\Communications\JsonRpcInterface;

class WithdrawStorage implements StorageAdapterInterface
{
    private const RPC_HISTORY = 'history';
    private const RPC_BALANCE = 'balance';
    private const RPC_ADDRESS_CODE = 'code';
    private const GET_USER_ID_BY_ADDRESS = 'get_user_id_by_address';
    private const GET_CRYPTO_INCOME = 'get_crypto_income';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var string */
    private $service;

    public function __construct(JsonRpcInterface $jsonRpc, string $service)
    {
        $this->jsonRpc = $jsonRpc;
        $this->service = $service;
    }

    /** {@inheritdoc} */
    public function requestHistory(int $id, int $offset, int $limit, int $fromTimestamp): array
    {
        return $this->sendRequest(self::RPC_HISTORY, [
            'id' => $id,
            'service' => $this->service,
            'offset' => $offset,
            'limit' => $limit,
            'fromTimestamp' => $fromTimestamp,
        ]);
    }

    /** {@inheritdoc} */
    public function requestBalance(string $symbol, string $networkSymbol): string
    {
        return $this->sendRequest(self::RPC_BALANCE, [
            'asset' => $symbol,
            'network' => $networkSymbol,
        ])['balance'] ?? '0';
    }

    public function requestAddressCode(string $address, String $crypto): string
    {
        return $this->sendRequest(
            self::RPC_ADDRESS_CODE,
            [
                    'address' => $address,
                    'crypto' => $crypto,
            ]
        )['code'];
    }

    public function requestUserId(string $address, string $cryptoNetwork): ?int
    {
        return $this->sendRequest(
            self::GET_USER_ID_BY_ADDRESS,
            [
                'address' => $address,
                'crypto' => $cryptoNetwork,
            ]
        )['userId'] ?? null;
    }

    public function requestCryptoIncome(string $crypto, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->sendRequest(self::GET_CRYPTO_INCOME, [
            'symbol' => $crypto,
            'fromTimestamp' => $from->getTimestamp(),
            'toTimestamp' => $to->getTimestamp(),
        ]);
    }

    private function sendRequest(string $method, array $params): array
    {
        $response = $this->jsonRpc->send($method, $params);

        if ($response->hasError()) {
            throw new \Exception($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }
}
