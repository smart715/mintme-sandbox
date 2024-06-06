<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\CoinifyCommunicator;
use App\Communications\RestRpcInterface;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Services\JwtService\JwtServiceInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

class CoinifyCommunicatorTest extends TestCase
{
    public function testSignupTraderSuccess(): void
    {
        $guzzleRestWrapper = $this->mockRestRpc(['offlineToken' => 'test']);

        $communicator = new CoinifyCommunicator(
            $guzzleRestWrapper,
            $this->mockJwtService($this->never()),
            1
        );
        $response = $communicator->signupTrader($this->mockUser($this->once(), $this->never()));

        $this->assertEquals('test', $response);
    }

    public function testSignupTraderExists(): void
    {
        $guzzleRestWrapper = $this->mockRestRpc(
            ['error' => 'trader_exists'],
            2
        );

        $this->expectException(ApiBadRequestException::class);

        $communicator = new CoinifyCommunicator(
            $guzzleRestWrapper,
            $this->mockJwtService($this->once()),
            1
        );
        $communicator->signupTrader($this->mockUser($this->exactly(3), $this->never()));
    }

    public function testSignUpTraderFailure(): void
    {
        $guzzleRestWrapper = $this->mockRestRpc(['test' => 'test']);

        $this->expectException(ApiBadRequestException::class);

        $communicator = new CoinifyCommunicator(
            $guzzleRestWrapper,
            $this->mockJwtService($this->never()),
            1
        );
        $communicator->signupTrader($this->mockUser($this->once(), $this->never()));
    }

    public function testGetRefreshTokenSuccess(): void
    {
        $guzzleRestWrapper = $this->mockRestRpc(['refresh_token' => 'test']);

        $communicator = new CoinifyCommunicator(
            $guzzleRestWrapper,
            $this->mockJwtService($this->never()),
            -1
        );

        $response = $communicator->getRefreshToken($this->mockUser($this->never(), $this->once()));

        $this->assertEquals('test', $response);
    }

    public function testGetRefreshTokenFailure(): void
    {
        $guzzleRestWrapper = $this->mockRestRpc(['test' => 'test']);

        $this->expectException(ApiBadRequestException::class);

        $communicator = new CoinifyCommunicator(
            $guzzleRestWrapper,
            $this->mockJwtService($this->never()),
            -1
        );
        $communicator->getRefreshToken($this->mockUser($this->never(), $this->once()));
    }

    private function mockRestRpc(
        array $responseData = [],
        int $sendCount = 1
    ): RestRpcInterface {
        $guzzleWrapper = $this->createMock(RestRpcInterface::class);
        $guzzleWrapper->expects($this->exactly($sendCount))
            ->method('send')
            ->willReturn(json_encode($responseData));

        return $guzzleWrapper;
    }

    private function mockJwtService(InvokedCount $createTokenCount): JwtServiceInterface
    {
        $jwtService = $this->createMock(JwtServiceInterface::class);
        $jwtService->expects($createTokenCount)
            ->method('createToken')
            ->willReturn('test.jwt.token');

        return $jwtService;
    }

    private function mockUser(InvokedCount $getEmailCount, InvokedCount $getCoinifyOfflineTokenCount): User
    {
        $user = $this->createMock(User::class);
        $user->expects($getEmailCount)->method('getEmail')->willReturn('TEST');
        $user->expects($getCoinifyOfflineTokenCount)->method('getCoinifyOfflineToken');

        return $user;
    }
}
