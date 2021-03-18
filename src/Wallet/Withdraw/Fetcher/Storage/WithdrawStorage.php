<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Fetcher\Storage;

use App\Communications\JsonRpcInterface;

class WithdrawStorage implements StorageAdapterInterface
{
    private const RPC_HISTORY = 'history';
    private const RPC_BALANCE = 'balance';
    private const RPC_ADDRESS_CODE = 'code';

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
    public function requestHistory(int $id, int $offset, int $limit): array
    {
        return $this->sendRequest(self::RPC_HISTORY, [
            'id' => $id,
            'service' => $this->service,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    /** {@inheritdoc} */
    public function requestBalance(string $symbol): string
    {
        return $this->sendRequest(self::RPC_BALANCE, [
            'crypto' => $symbol,
        ])['balance'] ?? '0';
    }

    public function requestAddressCode(string $address, String $crypto): bool
    {
        return '0x' === $this->sendRequest(
            self::RPC_ADDRESS_CODE,
            [
                    'address' => $address,
                    'crypto' => $crypto,
            ]
        )['code'];
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
