<?php

namespace App\Verify;

use App\Communications\Factory\HttpClientFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class WebsiteVerifier implements WebsiteVerifierInterface
{
    private const HTTP_OK = 200;
    private const MATCH_CODE = 1;

    /** @var HttpClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $timeoutSeconds;

    public function __construct(
        HttpClientFactoryInterface $clientFactory,
        LoggerInterface $logger,
        int $timeoutSeconds
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function verify(string $url, string $verificationToken): bool
    {
        $formatUrl = rtrim($url, '/').'/';
        try {
            $client = $this->clientFactory->createClient(['base_uri' => $formatUrl, 'timeout' => $this->timeoutSeconds]);
            $response = $client->request('GET', self::URI);
        } catch (GuzzleException $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }

        if (self::HTTP_OK === $response->getStatusCode()) {
            $expectedPattern = '/('.self::PREFIX.': '.$verificationToken.')/';
            return self::MATCH_CODE === preg_match($expectedPattern, $response->getBody()->getContents());
        }

        return false;
    }
}
