<?php declare(strict_types = 1);

namespace App\Utils\Verify;

use App\Communications\Factory\HttpClientFactoryInterface;
use App\Services\TranslatorService\TranslatorInterface;
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

    /** @var string */
    private $proxy;

    /** @var string[]|bool[] */
    private $error = [];

    private TranslatorInterface $translator;

    public function __construct(
        HttpClientFactoryInterface $clientFactory,
        LoggerInterface $logger,
        int $timeoutSeconds,
        string $proxy,
        TranslatorInterface $translator
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->proxy = $proxy;
        $this->translator = $translator;
    }

    public function verify(string $url, string $verificationToken): bool
    {
        foreach (self::URIS as $uri) {
            if ($this->verifyCommon($url, $verificationToken, $uri)) {
                return true;
            }
        }

        return false;
    }

    private function verifyCommon(string $url, string $verificationToken, string $uri): bool
    {
        $formatUrl = rtrim($url, '/').'/';

        try {
            $client = $this->clientFactory->createClient(
                [
                    'base_uri' => $formatUrl,
                    'timeout' => $this->timeoutSeconds,
                    'request.options' => ['proxy' => $this->proxy],
                ]
            );
            $response = $client->request('GET', $uri);
        } catch (\Throwable $exception) {
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

            $expectedPattern = '/^'.self::PREFIX.': '.$verificationToken.'$/';

            if (!preg_match($expectedPattern, $fileContent)) {
                $this->selectError(self::INVALID_VERIFICATION_CODE);

                return false;
            }

            $this->error = [];

            return true;
        }

        $this->selectError($response->getStatusCode());

        return false;
    }

    public function verifyAirdropPostLinkAction(string $url, string $message): bool
    {
        $client = $this->clientFactory->createClient([
            'timeout' => $this->timeoutSeconds,
            'request.options' => ['proxy' => $this->proxy],
        ]);

        try {
            $response = $client->request('GET', $url);
        } catch (\Throwable $e) {
            $this->selectError($e->getCode());
            $this->logger->error($e->getMessage());

            return false;
        }

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return false;
        }

        $fileContent = $response->getBody()->getContents();

        if (empty($fileContent)) {
            $this->selectError(Response::HTTP_NO_CONTENT);

            return false;
        }

        $expectedPattern = '#('.preg_quote($message).'(?!/airdrop))#';

        if (!preg_match($expectedPattern, $fileContent)) {
            $this->selectError(self::INVALID_VERIFICATION_CODE);

            return false;
        }

        return true;
    }

    private function selectError(int $code): void
    {
        if (Response::HTTP_NO_CONTENT === $code) {
            $this->setError(
                $this->translator->trans('token.website.verification_file.file_empty.title'),
                $this->translator->trans('token.website.verification_file.file_empty.details'),
                false
            );
        } elseif (self::INVALID_VERIFICATION_CODE === $code) {
            $this->setError(
                $this->translator->trans('token.website.verification_file.wrong_content.title'),
                $this->translator->trans('token.website.verification_file.wrong_content.details'),
                false
            );
        } else {
            $this->setError(
                $this->translator->trans('token.website.verification_file.http_status.title', ['%code%' => $code]),
                $this->translator->trans('token.website.verification_file.http_status.details')
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
