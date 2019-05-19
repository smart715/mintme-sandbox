<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;

interface RestRpcInterface
{
    /** @throws FetchException */
    public function send(string $path, string $method, array $requestParams = []): string;
}
