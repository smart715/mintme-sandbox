<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Component\HttpFoundation\Request;

class CoinmarketcapCommunicator implements CryptoSynchronizerInterface
{
    /** @var RestRpcInterface */
    private $rpc;

    public function __construct(RestRpcInterface $rpc)
    {
        $this->rpc = $rpc;
    }

    public function fetchCryptos(int $offset = 1, int $limit = 100): array
    {
        assert($offset > 0);
        assert($limit >= 1 && $limit <= 5000);

        $response = $this->rpc->send(
            'cryptocurrency/listings/latest',
            Request::METHOD_GET,
            [
                'query' => [
                    'limit' => $limit,
                    'start' => $offset,
                ],
            ]
        );

        $cryptos = json_decode($response, true)['data'];
        $names = [];

        array_walk($cryptos, function (array $res) use (&$names): void {
            $names[] = trim(strtolower($res['symbol']));
            $names[] = trim(strtolower($res['name']));
            $names[] = trim(strtolower($res['slug']));
        });

        return $names;
    }
}
