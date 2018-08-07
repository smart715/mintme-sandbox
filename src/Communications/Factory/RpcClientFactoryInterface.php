<?php

namespace App\Communications\Factory;

use Graze\GuzzleHttp\JsonRpc\Client;

interface RpcClientFactoryInterface
{
    public function createClient(string $url, array $parameters): Client;
}