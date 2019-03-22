<?php declare(strict_types = 1);

namespace App\Communications\Factory;

use Graze\GuzzleHttp\JsonRpc\ClientInterface;

interface RpcClientFactoryInterface
{
    public function createClient(string $url, array $parameters): ClientInterface;
}
