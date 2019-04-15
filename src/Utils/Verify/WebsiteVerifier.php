<?php declare(strict_types = 1);

namespace App\Utils\Verify;

use App\Communications\Factory\HttpClientFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class WebsiteVerifier implements WebsiteVerifierInterface
{
    private const INVALID_VERIFICATION_CODE = 2;

    /** @var HttpClientFactoryInterface */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $timeoutSeconds;

    /** @var string[]|bool[] */
    private $error = [];

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
            $client = $this->clientFactory->createClient(
                ['base_uri' => $formatUrl, 'timeout' => $this->timeoutSeconds]
            );
            $response = $client->request('GET', self::URI);
        } catch (GuzzleException $exception) {
            $this->selectError($exception->getCode());
            $this->logger->error($exception->getMessage());

            return false;
        }

        if (Response::HTTP_OK === $response->getStatusCode()) {
            $fileContent = $response->getBody()->getContents();

            if (empty($fileContent)) {
                $this->selectError(Response::HTTP_NO_CONTENT);

                return false;
            }

            $expectedPattern = '/('.self::PREFIX.': '.$verificationToken.')/';

            if (!preg_match($expectedPattern, $fileContent)) {
                $this->selectError(self::INVALID_VERIFICATION_CODE);

                return false;
            }

            return true;
        }

        $this->selectError($response->getStatusCode());

        return false;
    }

    private function selectError(int $code): void
    {
        if (Response::HTTP_NO_CONTENT === $code) {
            $this->setError(
                'Your verification file is empty.',
                'Bot checks to see if your verification file has the same filename and content as 
                the file provided on the Verification page. If the file name or content does not match the 
                HTML file provided, we won\'t be able to verify your site ownership. Please download the 
                verification file, and upload it to the specified location without any modifications.',
                false
            );
        } elseif (self::INVALID_VERIFICATION_CODE === $code) {
            $this->setError(
                'Your verification file has the wrong content.',
                'Bot checks to see if your verification file has the same filename and content as the 
                file provided. If the file name or content does not match the HTML file provided, we won\'t
                 be able to verify your site ownership. Please download the verification file, and upload it 
                 to the specified location without any modifications.',
                false
            );
        } else {
            $this->setError(
                sprintf('Your verification file returns a HTTP status code of %s instead of 200(OK).', $code),
                'If your server returns a HTTP status code other than 200(OK) for your HTML verification file,
                 Bot will not be able to verify that it has the expected filename and content. More information about 
                 HTTP status codes.'
            );
        }
    }

    private function setError(string $title, string $details, bool $visibleHttpUrl = true): void
    {
        $this->error = [
            'title' => $title,
            'details' => $details,
            'visibleHttpUrl' => $visibleHttpUrl,
        ];
    }

    public function getError(): array
    {
        return $this->error;
    }
}
