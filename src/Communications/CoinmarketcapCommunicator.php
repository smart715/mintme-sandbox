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
        $blacklisted = [];
        $blacklisted['names'] = [];
        $blacklisted['symbols'] = [];

        array_walk($cryptos, static function (array $res) use (&$blacklisted): void {
            $blacklisted['names'][] = strtolower(trim($res['name']));
            $blacklisted['symbols'][] = strtolower(trim($res['symbol']));
        });

        $blacklisted['names'] = array_unique($blacklisted['names']);
        $blacklisted['symbols'] = array_unique($blacklisted['symbols']);
        
        return $blacklisted;
    }
}
