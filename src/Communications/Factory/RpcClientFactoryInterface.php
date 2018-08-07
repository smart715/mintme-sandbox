<?php

namespace App\Communications\Factory;

use Graze\GuzzleHttp\JsonRpc\ClientInterface;

interface RpcClientFactoryInterface
{
    public function createClient(string $url, array $parameters): ClientInterface;
}
