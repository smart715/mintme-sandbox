<?php

namespace App\Tests\Communications;

use App\Communications\Factory\RpcClientFactoryInterface;
use App\Communications\GuzzleWrapper;
use App\Communications\JsonRpcResponse;
use App\Utils\RandomNumber;
use Graze\GuzzleHttp\JsonRpc\ClientInterface;
use Graze\GuzzleHttp\JsonRpc\Message\RequestInterface;
use Graze\GuzzleHttp\JsonRpc\Message\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class GuzzleWrapperTest extends TestCase
{
    public function testClientBuiltOnce(): void
    {
        $url = 'http://localhost:8080';
        $timeout = 10;

        $stream = $this->getStreamMock('{"id": 1, "result": "stubResult"}');
        $client = $this->getClientMock($stream);

        $clientFactory = $this->createMock(RpcClientFactoryInterface::class);
        $clientFactory->expects($this->once())
            ->method('createClient')
            ->with($this->equalTo($url), $this->equalTo(['timeout' => $timeout]))
            ->willReturn($client);

        $wrapper = new GuzzleWrapper(
            $this->getRandomNumber(),
            $clientFactory,
            $this->getLoggerMock(),
            $url,
            $timeout
        );

        for ($i = 0; $i < 5; $i++) {
            $wrapper->send('stubmethod', [['stubparam1', 'stubparam2']]);
        }
    }

    public function testSendSuccessful(): void
    {
        $url = 'http://localhost:8080';
        $timeout = 10;
        $method = 'stubMethod';
        $params = [['param1', 'param2']];

        $stream = $this->getStreamMock('{"id": 1, "result": "stubResult"}');
        $client = $this->getClientMock($stream);
        $client->method('request')
            ->with($this->anything(), $this->equalTo($method), $this->equalTo($params));

        $clientFactory = $this->createMock(RpcClientFactoryInterface::class);
        $clientFactory->method('createClient')
            ->with($url, ['timeout' => $timeout])
            ->willReturn($client);

        $wrapper = new GuzzleWrapper(
            $this->getRandomNumber(),
            $clientFactory,
            $this->getLoggerMock(),
            $url,
            $timeout
        );
        $this->assertInstanceOf(JsonRpcResponse::class, $wrapper->send($method, $params));
    }

    public function testSendFails(): void
    {
        $url = 'http://localhost:8080';
        $timeout = 10;
        $method = 'stubMethod';
        $params = [['param1', 'param2']];

        $clientMock = $this->createMock(ClientInterface::class);
        $clientMock->method('request')
            ->will($this->throwException(new \Exception()));

        $clientFactory = $this->createMock(RpcClientFactoryInterface::class);
        $clientFactory->method('createClient')
            ->with($url, ['timeout' => $timeout])
            ->willReturn($clientMock);

        $wrapper = new GuzzleWrapper(
            $this->getRandomNumber(),
            $clientFactory,
            $this->getLoggerMock(),
            $url,
            $timeout
        );

        $this->expectException(\Throwable::class);
        $wrapper->send($method, $params);
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
        $clientMock->method('send')
            ->willReturn($this->getResponseMock($stream));
        $clientMock->method('request')
            ->willReturn($this->createMock(RequestInterface::class));
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

    /** @return MockObject|RandomNumber */
    private function getRandomNumber(): RandomNumber
    {
        return $this->createMock(RandomNumber::class);
    }
}
