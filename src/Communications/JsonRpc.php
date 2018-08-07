<?php

namespace App\Communications;

interface JsonRpc
{
    public function send(string $methodName, array $requestParams): JsonRpcResponse;
}
