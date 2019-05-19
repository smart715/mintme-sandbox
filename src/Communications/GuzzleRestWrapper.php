<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Communications\Factory\HttpClientFactory;
use Exception;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class GuzzleRestWrapper implements RestRpcInterface
{
    /** @var HttpClientFactory */
    private $clientFactory;

    /** @var ClientInterface|null */
    private $client = null;

    /** @var int */
    private $timeoutSeconds;

    /** @var string */
    private $url;

    /** @var LoggerInterface */
    private $logger;

    /** @var array<string> */
    private $auth;

    /** @var array<string> */
    private $headers;

    public function __construct(
        HttpClientFactory $clientFactory,
        int $timeoutSeconds,
        string $url,
        LoggerInterface $logger,
        array $auth = [],
        array $headers = []
    ) {
        $this->clientFactory = $clientFactory;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->url = $url;
        $this->logger = $logger;
        $this->auth = $auth;
        $this->headers = $headers;
    }

    public function send(string $path, string $method, array $requestParams = []): string
    {
        try {
            $this->constructClient();
            $response = $this->sendRequest($path, $method, $requestParams);

            return $response->getBody()->getContents();
        } catch (Throwable $e) {
            $this->logger->error(
                "Failed to get response from '{}' with method '$method' and params: " .
                json_encode($requestParams) . " Error: " . $e->getCode() .". ". $e->getMessage()
            );

            throw new FetchException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param array<mixed> $requestParams
     * @throws Exception
     */
    private function sendRequest(string $path, string $method, array $requestParams): ResponseInterface
    {
        return $this->client->request(
            $method,
            $this->buildUrl($path),
            $requestParams
        );
    }

    private function constructClient(): void
    {
        if (!is_null($this->client)) {
            return;
        }

        $parameters = [
            'timeout' => $this->timeoutSeconds,
        ];

        if ($this->auth) {
            $parameters['auth'] = $this->auth;
        }

        if ($this->headers) {
            $parameters['headers'] = $this->headers;
        }

        $this->client = $this->clientFactory->createClient($parameters);
    }

    private function buildUrl(string $path): string
    {
        return trim($this->url, '/') . '/' . $path;
    }
}
