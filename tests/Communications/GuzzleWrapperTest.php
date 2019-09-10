<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\Exception\FetchException;
use App\Communications\Factory\RpcClientFactoryInterface;
use App\Communications\GuzzleWrapper;
use App\Communications\JsonRpcResponse;
use App\Logger\UserActionLogger;
use App\Utils\RandomNumber;
use Graze\GuzzleHttp\JsonRpc\ClientInterface;
use Graze\GuzzleHttp\JsonRpc\Message\RequestInterface;
use Graze\GuzzleHttp\JsonRpc\Message\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

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
            $this->getUserActionLoggerMock(),
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
            $this->getUserActionLoggerMock(),
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
            $this->getUserActionLoggerMock(),
            $url,
            $timeout
        );

        $session = new Session(new NativeSessionStorage(), new AttributeBag());
        $session->get('creation_token');

        $this->expectException(\Throwable::class);
        $wrapper->send($method, $params);

        $session->clear();
    }

    /** @dataProvider failedResponseProvider */
    public function testSendFailsWithEptyResponse(?ResponseInterface $res): void
    {
        $clientMock = $this->createMock(ClientInterface::class);
        $clientMock->method('request')
            ->willReturn($this->createMock(RequestInterface::class));
        $clientMock->method('send')
            ->willReturn($res);

        $clientFactory = $this->createMock(RpcClientFactoryInterface::class);
        $clientFactory->expects($this->once())
            ->method('createClient')
            ->willReturn($clientMock);

        $wrapper = new GuzzleWrapper(
            $this->getRandomNumber(),
            $clientFactory,
            $this->getLoggerMock(),
            $this->getUserActionLoggerMock(),
            'http://localhost:8080',
            10,
            ['auth' => ['type' => 'basic']]
        );

        $this->expectException(FetchException::class);
        $wrapper->send('stubMethod', [['param1', 'param2']]);
    }

    public function failedResponseProvider(): array
    {
        return [[null], [$this->getResponseMock($this->getStreamMock("['foo']"))]];
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

    /** @return MockObject|UserActionLogger */
    private function getUserActionLoggerMock(): UserActionLogger
    {
        return $this->createMock(UserActionLogger::class);
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
