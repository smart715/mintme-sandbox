<?php declare(strict_types = 1);

namespace App\Tests\Utils\Verify;

use App\Communications\Factory\HttpClientFactoryInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Verify\WebsiteVerifier;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class WebsiteVerifierTest extends TestCase
{
    /**
     * @dataProvider verifySuccessProvider
     */
    public function testVerifySuccess(string $content, string $uri): void
    {
        $verifier = $this->createVerifier(false, 200, $content);

        $result = $verifier->verify('https://www.google.com', $uri);

        $this->assertTrue($result);
        $this->assertEquals([], $verifier->getError());
    }

    public function verifySuccessProvider(): array
    {
        return [
            ['mintme-site-verification: TEST', 'TEST'],
            ["mintme-site-verification: TEST\n", 'TEST'],
        ];
    }

    /**
     * @dataProvider verifyFailureProvider
     */
    public function testVerifyFailure(
        bool $exc,
        int $code,
        string $content,
        int $errorCount,
        string $uri,
        string $error
    ): void {
        $verifier = $this->createVerifier($exc, $code, $content, $errorCount);

        $result = $verifier->verify('https://www.google.com', $uri);

        $this->assertFalse($result);
        $this->assertEquals(
            $error,
            $verifier->getError()['title']
        );
    }

    public function verifyFailureProvider(): array
    {
        return [
            [false, 200, 'mintme-site-verification: TEST2', 0, 'TEST',
            'token.website.verification_file.wrong_content.title'], // shorter
            [false, 200, 'mintme-site-verification: TES', 0, 'TEST',
            'token.website.verification_file.wrong_content.title'], // longer
            [true, 0, 'TEST', 3, 'TEST',
            'token.website.verification_file.http_status.title'], // exception
            [false, 200, '', 0, 'TEST',
            'token.website.verification_file.file_empty.title'], // empty content
            [false, 200, '/TEST', 0, 'TEST',
            'token.website.verification_file.wrong_content.title'], // wrong content
            [false, 201, '/TEST', 0, 'TEST',
            'token.website.verification_file.http_status.title'], // bad code
        ];
    }

    public function testVerifyAirdropPostLinkAction(): void
    {
        $verifier = $this->createVerifier(false, 200, '#(TEST(?!/airdrop))#');

        $result = $verifier->verifyAirdropPostLinkAction('TEST', 'TEST');

        $this->assertTrue($result);
        $this->assertEquals([], $verifier->getError());
    }


    public function testVerifyAirdropPostLinkActionWithExceptionWillFail(): void
    {
        $verifier = $this->createVerifier(true, 200, '#(TEST(?!/airdrop))#', 1);

        $result = $verifier->verifyAirdropPostLinkAction('TEST', 'TEST');

        $this->assertFalse($result);
        $this->assertEquals(
            'token.website.verification_file.http_status.title',
            $verifier->getError()['title']
        );
    }

    public function testVerifyAirdropPostLinkActionWillFailWithout200StatusCode(): void
    {
        $verifier = $this->createVerifier(false, 201, '#(TEST(?!/airdrop))#');

        $result = $verifier->verifyAirdropPostLinkAction('TEST', 'TEST');

        $this->assertFalse($result);
        $this->assertEquals(
            [],
            $verifier->getError()
        );
    }

    public function testVerifyAirdropPostLinkActionWillFailWithoutBody(): void
    {
        $verifier = $this->createVerifier(false, 200, '');

        $result = $verifier->verifyAirdropPostLinkAction('TEST', 'TEST');

        $this->assertFalse($result);
        $this->assertEquals(
            'token.website.verification_file.file_empty.title',
            $verifier->getError()['title']
        );
    }

    public function testVerifyAirdropPostLinkActionWillFailWithIncorrectBody(): void
    {
        $verifier = $this->createVerifier(false, 200, 'Test');

        $result = $verifier->verifyAirdropPostLinkAction('TEST', 'TEST');

        $this->assertFalse($result);
        $this->assertEquals(
            'token.website.verification_file.wrong_content.title',
            $verifier->getError()['title']
        );
    }

    private function createVerifier(
        bool $throwError = false,
        int $statusCode = 200,
        string $content = '',
        int $errorCount = 0
    ): WebsiteVerifier {
        $response = $this->mockResponse($statusCode, $content);

        return new WebsiteVerifier(
            $this->mockHttpClientFactory($throwError, $response),
            $this->mockLogger($errorCount),
            10,
            '',
            $this->mockTranslator()
        );
    }

    private function mockHttpClientFactory(
        bool $throwErrors = false,
        ?ResponseInterface $response = null
    ): HttpClientFactoryInterface {
        $httpClientFactory = $this->createMock(HttpClientFactoryInterface::class);
        $httpClientFactory->expects($this->any())
            ->method('createClient')
            ->willReturn($this->mockClient($throwErrors, $response));

        return $httpClientFactory;
    }

    private function mockLogger(int $errorCount = 0): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly($errorCount))->method('error');

        return $logger;
    }

    private function mockClient(?bool $throwErrors, ?ResponseInterface $response): Client
    {
        $client = $this->createMock(Client::class);

        if ($throwErrors) {
            $client->expects($this->any())
                ->method('request')
                ->willThrowException(new \Exception());
        } else {
            $client->expects($this->any())
                ->method('request')
                ->willReturn($response);
        }

        return $client;
    }

    private function mockResponse(int $statusCode = 200, string $content = ''): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($this->mockStream($content));

        return $response;
    }

    private function mockStream(string $content): StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($content);

        return $stream;
    }

    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(function ($message) {
            return $message;
        });

        return $translator;
    }
}
