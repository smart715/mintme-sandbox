<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Utils\RandomNumberInterface;
use Exception;
use Graze\GuzzleHttp\JsonRpc\ClientInterface;
use Graze\GuzzleHttp\JsonRpc\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class GuzzleWrapper implements JsonRpcInterface
{
    /** @var ClientInterface|null */
    private $client = null;

    /** @var Factory\RpcClientFactoryInterface */
    private $clientFactory;

    /** @var string */
    private $url;

    /** @var int */
    private $timeoutSeconds;

    /** @var RandomNumberInterface */
    private $random;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        RandomNumberInterface $randomNumber,
        Factory\RpcClientFactoryInterface $clientFactory,
        LoggerInterface $logger,
        string $url,
        int $timeoutSeconds
    ) {
        $this->clientFactory = $clientFactory;
        $this->url = $url;
        $this->logger = $logger;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->random = $randomNumber;
    }

    public function send(string $methodName, array $requestParams): JsonRpcResponse
    {
        try {
            $this->constructClient();
            $response = $this->sendRequest($methodName, $requestParams);

            return $this->parseResponse($response);
        } catch (\Throwable $e) {
            $this->logger->error(
                "Failed to get response from '$this->url' with method '$methodName' and params: " .
                json_encode($requestParams) . " Error: " . $e->getCode() .". ". $e->getMessage()
            );

            throw new FetchException($e->getMessage(), $e->getCode());
        }
    }

    private function sendRequest(string $methodName, array $params): ResponseInterface
    {
        $request = $this->client->request(
            $this->random->getNumber(),
            $methodName,
            $params
        );
        $response = $this->client->send($request);

        if (null === $response) {
            throw new Exception('No response present');
        }

        return $response;
    }

    /** @throws \Throwable */
    private function parseResponse(ResponseInterface $response): JsonRpcResponse
    {
        try {
            return JsonRpcResponse::parse($response->getBody()->getContents());
        } catch (\Throwable $exception) {
            $this->logger->error(
                "Invalid JSON format from response: {$response->getBody()->getContents()}"
            );

            throw $exception;
        }
    }

    private function constructClient(): void
    {
        if (!is_null($this->client)) {
            return;
        }

        $this->client = $this->clientFactory->createClient($this->url, [
            'timeout' => $this->timeoutSeconds,
        ]);
    }
}
