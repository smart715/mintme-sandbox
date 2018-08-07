<?php

namespace App\Communications\Factory;

use Graze\GuzzleHttp\JsonRpc\Client;
use Graze\GuzzleHttp\JsonRpc\ClientInterface;

class GuzzleRpcClientFactory implements RpcClientFactoryInterface
{
    public function createClient(string $url, array $parameters): ClientInterface
    {
        return Client::factory($url, $parameters);
    }
}
