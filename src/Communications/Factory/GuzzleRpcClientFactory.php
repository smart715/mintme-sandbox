<?php

namespace App\Communications\Factory;

use Graze\GuzzleHttp\JsonRpc\Client;

class GuzzleRpcClientFactory implements RpcClientFactoryInterface
{
    public function createClient(string $url, array $parameters): Client
    {
        return Client::factory($url, $parameters);
    }
}