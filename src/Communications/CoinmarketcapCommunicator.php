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

    public function fetchCryptos(): array
    {
        $response = $this->rpc->send(
            'coins/list',
            Request::METHOD_GET
        );

        $cryptos = json_decode($response, true);
        $names = [];

        array_walk($cryptos, function (array $res) use (&$names): void {
            $names[] = trim(strtolower($res['id']));
            $names[] = trim(strtolower($res['symbol']));
            $names[] = trim(strtolower($res['name']));
        });

        return array_unique($names);
    }
}
