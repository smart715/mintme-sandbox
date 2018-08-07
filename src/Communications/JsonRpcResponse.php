<?php

namespace App\Communications;

use Exception;

class JsonRpcResponse
{
    /** @var mixed[] */
    private $response;

    private function __construct(array $response)
    {
        $this->response = $response;
    }

    public static function parse(string $response): self
    {
        $decodedResponse = json_decode($response, true);

        if (JSON_ERROR_NONE !== json_last_error())
            throw new Exception('Failed to parse response');

        return new self($decodedResponse);
    }

    /** @return mixed */
    public function getResult()
    {
        return $this->response['result'] ?? [];
    }

    public function getError(): array
    {
        return $this->response['error'] ?? [];
    }

    public function hasResult(): bool
    {
        return !empty($this->getResult());
    }

    public function hasError(): bool
    {
        return !empty($this->getError());
    }
}
