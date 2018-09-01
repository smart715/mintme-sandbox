<?php

namespace App\Communications\Factory;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class HttpClientFactory implements HttpClientFactoryInterface
{
    public function createClient(array $parameters): ClientInterface
    {
        return new Client($parameters);
    }
}
