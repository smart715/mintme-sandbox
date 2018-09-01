<?php

namespace App\Communications\Factory;

use GuzzleHttp\ClientInterface;

interface HttpClientFactoryInterface
{
    public function createClient(array $parameters): ClientInterface;
}
