<?php declare(strict_types = 1);

namespace App\Communications\Factory;

use GuzzleHttp\ClientInterface;

interface HttpClientFactoryInterface
{
    public function createClient(array $parameters): ClientInterface;
}
