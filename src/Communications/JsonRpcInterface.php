<?php

namespace App\Communications;

interface JsonRpcInterface
{
    public function send(string $methodName, array $requestParams): JsonRpcResponse;
}
