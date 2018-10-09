<?php

namespace App\Communications;

use App\Communications\Exception\FetchException;

interface JsonRpcInterface
{
    /** @throws FetchException */
    public function send(string $methodName, array $requestParams): JsonRpcResponse;
}
