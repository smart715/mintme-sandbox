<?php declare(strict_types = 1);

namespace App\Tests\SmartContract;

use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\SmartContract\Config\Config;
use App\SmartContract\ContractHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContractHandlerTest extends TestCase
{
    public function testDeploy(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'deploy',
                [
                    'name' => 'foo',
                    'decimals' => 4,
                    'mintDestination' => 'foobarbaz',
                    'releasedAtCreation' => '10000000000',
                    'releasePeriod' => 10,
                ]
            )
            ->willReturn($this->mockResponse(false, ['address' => 'foo123']));

        $handler = new ContractHandler(
            $rpc,
            $this->mockConfig(),
            $this->mockLoggerInterface()
        );

        $result = $handler->deploy($this->mockToken(true));

        $this->assertEquals($result->getAddress(), 'foo123');
    }

    public function testDeployThrowExceptionIfTokenHasNoReleasePeriod(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->never())->method('send');

        $handler = new ContractHandler(
            $rpc,
            $this->mockConfig(),
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
                    'decimals' => 4,
                    'mintDestination' => 'foobarbaz',
                    'releasedAtCreation' => '10000000000',
                    'releasePeriod' => 10,
                ]
            )
            ->willReturn($this->mockResponse(false, ['Bar']));

        $handler = new ContractHandler(
            $rpc,
            $this->mockConfig(),
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
                    'decimals' => 4,
                    'mintDestination' => 'foobarbaz',
                    'releasedAtCreation' => '10000000000',
                    'releasePeriod' => 10,
                ]
            )
            ->willReturn($this->mockResponse(true, ['address' => 'foo123']));

        $handler = new ContractHandler(
            $rpc,
            $this->mockConfig(),
            $this->mockLoggerInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->deploy($this->mockToken(true));
    }

    public function testUpdateMinDestination(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'update_mint_destination',
                [
                    'tokenContract' => '0x123',
                    'mintDestination' => '0x456',
                    'lock'=> false,
                ]
            );

        $handler = new ContractHandler(
            $rpc,
            $this->mockConfig(),
            $this->mockLoggerInterface()
        );

        $handler->updateMinDestination($this->mockToken(true), '0x456', false);
    }

    public function testUpdateMinDestinationWithLocked(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->never())->method('send');

        $handler = new ContractHandler(
            $rpc,
            $this->mockConfig(),
            $this->mockLoggerInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->updateMinDestination($this->mockToken(true, '0x123', true), '0x456', false);
    }

    public function testUpdateMinDestinationWithResponseError(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'update_mint_destination',
                [
                    'tokenContract' => '0x123',
                    'mintDestination' => '0x456',
                    'lock'=> false,
                ]
            )
            ->willReturn($this->mockResponse(true));

        $handler = new ContractHandler(
            $rpc,
            $this->mockConfig(),
            $this->mockLoggerInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->updateMinDestination($this->mockToken(true, '0x123', false), '0x456', false);
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
    private function mockToken(bool $hasReleasePeriod, string $address = '0x123', bool $minLocked = false): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getName')->willReturn('foo');
        $token->method('getAddress')->willReturn($address);
        $token->method('isMinDestinationLocked')->willReturn($minLocked);

        if (!$hasReleasePeriod) {
            $token->method('getLockIn')->willReturn(null);
        }

        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getReleasePeriod')->willReturn(10);
        $token->method('getLockIn')->willReturn($lockIn);

        return $token;
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
