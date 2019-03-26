<?php declare(strict_types = 1);

namespace App\Verify;

use App\Communications\Factory\HttpClientFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class WebsiteVerifier implements WebsiteVerifierInterface
{
    private const MATCH_CODE = 1;

    /** @var HttpClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $timeoutSeconds;

    /** @var string[] */
    private $errors = [];

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
            $this->addError($exception->getCode());
            $this->logger->error($exception->getMessage());

            return false;
        }

        if (Response::HTTP_OK === $response->getStatusCode()) {
            $expectedPattern = '/('.self::PREFIX.': '.$verificationToken.')/';

            return self::MATCH_CODE === preg_match($expectedPattern, $response->getBody()->getContents());
        }

        return false;
    }

    public function addError(int $code): void
    {
        if (Response::HTTP_NOT_FOUND === $code) {
            $this->errors[] = 'File not found';
        } elseif (Response::HTTP_FORBIDDEN === $code) {
            $this->errors[] = 'Access denied';
        } else {
            $this->errors[] = 'Website couldn\'t be confirmed, try again';
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
