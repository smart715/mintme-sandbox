<?php

namespace App\Communications;

use App\Communications\Exception\FetchException;
use Exception;
use Graze\GuzzleHttp\JsonRpc\ClientInterface;
use Graze\GuzzleHttp\JsonRpc\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

class GuzzleWrapper implements JsonRpc
{
    /** @var ClientInterface|null */
    private $client = null;

    /** @var Factory\RpcClientFactoryInterface */
    private $clientFactory;

    /** @var string */
    private $url;

    /** @var int */
    private $timeoutSeconds;

    public function __construct(
        Factory\RpcClientFactoryInterface $clientFactory,
        string $url,
        int $timeoutSeconds
    ) {
        $this->clientFactory = $clientFactory;
        $this->url = $url;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function send(string $methodName, array $requestParams): JsonRpcResponse
    {
        try {
            $this->constructClient();
            $response = $this->sendRequest($methodName, $requestParams);
            return $this->parseResponse($response);
        } catch (\Throwable $e) {
            throw new FetchException($e->getMessage(), $e->getCode());
        }
    }

    private function sendRequest(string $methodName, array $params): ResponseInterface
    {
        $request = $this->client->request(
            Uuid::uuid4()->toString(),
            $methodName,
            $params
        );
        $response = $this->client->send($request);

        if (null === $response) {
            throw new Exception('No response present');
        }

        return $response;
    }

    private function parseResponse(ResponseInterface $response): JsonRpcResponse
    {
        return JsonRpcResponse::parse($response->getBody()->getContents());
    }

    private function constructClient(): void
    {
        if (!is_null($this->client))
            return;

        $this->client = $this->clientFactory->createClient($this->url, [
            'timeout' => $this->timeoutSeconds,
        ]);
    }
}
