<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\Exception\FetchException;
use App\Communications\Factory\HttpClientFactoryInterface;
use App\Communications\GuzzleRestWrapper;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class GuzzleRestWrapperTest extends TestCase
{
    public function testClientBuiltOnce(): void
    {
        $stream = $this->getStreamMock('foo');
        $client = $this->getClientMock($stream);

        $clientFactory = $this->createMock(HttpClientFactoryInterface::class);
        $clientFactory->expects($this->once())
            ->method('createClient')
            ->willReturn($client);

        $wrapper = new GuzzleRestWrapper(
            $clientFactory,
            10,
            'http://localhost:8080',
            $this->getLoggerMock(),
            ['auth' => ['type' => 'basic']],
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        array_map(function () use ($wrapper): void {
            $this->assertEquals('foo', $wrapper->send('/foo', 'POST', []));
        }, range(1, 5));
    }

    public function testClientWithFails(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('request')
            ->willThrowException(new FetchException());

        $clientFactory = $this->createMock(HttpClientFactoryInterface::class);
        $clientFactory->expects($this->once())
            ->method('createClient')
            ->willReturn($client);

        $wrapper = new GuzzleRestWrapper(
            $clientFactory,
            10,
            'http://localhost:8080',
            $this->getLoggerMock(),
            ['auth' => ['type' => 'basic']],
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $this->expectException(FetchException::class);
        $wrapper->send('/foo', 'POST', []);
    }

    private function getStreamMock(string $content): StreamInterface
    {
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('getContents')->willReturn($content);

        return $streamMock;
    }

    /** @return MockObject|LoggerInterface */
    private function getLoggerMock(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @param StreamInterface $stream
     * @return MockObject|ClientInterface
     */
    private function getClientMock(StreamInterface $stream)
    {
        $clientMock = $this->createMock(ClientInterface::class);
        $clientMock->method('request')
            ->willReturn($this->getResponseMock($stream));

        return $clientMock;
    }

    /**
     * @param StreamInterface $stream
     * @return MockObject|ResponseInterface
     */
    private function getResponseMock(StreamInterface $stream)
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getBody')->willReturn($stream);

        return $responseMock;
    }
}
