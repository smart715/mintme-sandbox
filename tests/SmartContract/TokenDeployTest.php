<?php declare(strict_types = 1);

namespace App\Tests\SmartContract;

use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\SmartContract\Config\Config;
use App\SmartContract\TokenDeploy;
use App\Utils\Converter\TokenNameConverter;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TokenDeployTest extends TestCase
{
    public function testDeploy(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'deploy',
                [
                    'name' => 'foo',
                    'symbol' => 'TOK999',
                    'decimals' => 4,
                    'mintDestination' => 'foobarbaz',
                    'releasedAtCreation' => '10000000000',
                    'releasedPeriod' => 10,
                ]
            )
            ->willReturn($this->mockResponse(false, [
                    'address' => 'foo123',
                    'transactionHash' => 'bar123',
            ]));

        $handler = new TokenDeploy(
            $rpc,
            $this->mockConfig(),
            $this->mockTokenNameConverter(),
            $this->mockLoggerInterface()
        );

        $result = $handler->deploy($this->mockToken(true));

        $this->assertEquals($result->getAddress(), 'foo123');
        $this->assertEquals($result->getTransactionHash(), 'bar123');
    }

    public function testDeployThrowExceptionIfTokenHasNoReleasePeriod(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->never())->method('send');

        $handler = new TokenDeploy(
            $rpc,
            $this->mockConfig(),
            $this->mockTokenNameConverter(),
            $this->mockLoggerInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->deploy($this->mockToken(false));
    }

    public function testDeployThrowExceptionIfInvalidResponse(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'deploy',
                [
                    'name' => 'foo',
                    'symbol' => 'TOK999',
                    'decimals' => 4,
                    'mintDestination' => 'foobarbaz',
                    'releasedAtCreation' => '10000000000',
                    'releasedPeriod' => 10,
                ]
            )
            ->willReturn($this->mockResponse(false, ['Bar']));

        $handler = new TokenDeploy(
            $rpc,
            $this->mockConfig(),
            $this->mockTokenNameConverter(),
            $this->mockLoggerInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->deploy($this->mockToken(true));
    }

    public function testDeployThrowExceptionIfResponseError(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'deploy',
                [
                    'name' => 'foo',
                    'symbol' => 'TOK999',
                    'decimals' => 4,
                    'mintDestination' => 'foobarbaz',
                    'releasedAtCreation' => '10000000000',
                    'releasedPeriod' => 10,
                ]
            )
            ->willReturn($this->mockResponse(true, [
                'address' => 'foo123',
                'transactionHash' => 'bar123',
            ]));

        $handler = new TokenDeploy(
            $rpc,
            $this->mockConfig(),
            $this->mockTokenNameConverter(),
            $this->mockLoggerInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->deploy($this->mockToken(true));
    }

    /** @return Config|MockObject */
    private function mockConfig(): Config
    {
        $config = $this->createMock(Config::class);

        $config->method('getTokenPrecision')->willReturn(4);
        $config->method('getMintmeAddress')->willReturn('foobarbaz');
        $config->method('getTokenQuantity')->willReturn('1000000');

        return $config;
    }

    /** @return Token|MockObject */
    private function mockToken(bool $hasReleasePeriod): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getName')->willReturn('foo');

        if (!$hasReleasePeriod) {
            $token->method('getLockIn')->willReturn(null);
        }

        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getReleasePeriod')->willReturn(10);
        $token->method('getLockIn')->willReturn($lockIn);

        return $token;
    }

    /** @return TokenNameConverter|MockObject */
    private function mockTokenNameConverter(): TokenNameConverter
    {
        $converter = $this->createMock(TokenNameConverter::class);
        $converter->method('convert')->willReturn('TOK999');

        return $converter;
    }

    /** @return LoggerInterface|MockObject */
    private function mockLoggerInterface(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    /** @return MockObject|JsonRpcResponse */
    private function mockResponse(bool $hasError, array $result = []): JsonRpcResponse
    {
        $response = $this->createMock(JsonRpcResponse::class);
        $response->method('hasError')->willReturn($hasError);
        $response->method('getResult')->willReturn($result);

        return $response;
    }

    /** @return MockObject|JsonRpcInterface */
    private function mockRpc(): JsonRpcInterface
    {
        return $this->createMock(JsonRpcInterface::class);
    }
}
