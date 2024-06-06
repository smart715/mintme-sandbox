<?php declare(strict_types = 1);

namespace App\Tests\Communications;

use App\Communications\DiscordOAuthClient;
use App\Communications\RestRpcInterface;
use PHPUnit\Framework\TestCase;

class DiscordOAuthClientTest extends TestCase
{
    public function testGetAccessTokenSuccess(): void
    {
        $client = new DiscordOAuthClient(
            'test',
            'test',
            'test.com/',
            $this->mockRestRpc(['access_token' => 'TEST_RESPONSE'])
        );

        $this->assertEquals(
            'TEST_RESPONSE',
            $client->getAccessToken('test', 'test')
        );
    }

    public function testGenerateAuthUrl(): void
    {
        $client = new DiscordOAuthClient(
            'CLIENT_TEST',
            'SECRET_TEST',
            'https://www.test.com/',
            $this->mockRestRpc()
        );

        $this->assertEquals(
            'https://www.test.com/authorize?'.
            'client_id=CLIENT_TEST&'.
            'redirect_uri=https%3A%2F%2Fwww.test.com&'.
            'scope=SCOPE_TEST&'.
            'response_type=code',
            $client->generateAuthUrl('SCOPE_TEST', 'https://www.test.com')
        );
    }

    private function mockRestRpc(?array $responseData = null): RestRpcInterface
    {
        $restRpc = $this->createMock(RestRpcInterface::class);
        $restRpc->expects($responseData ? $this->once() : $this->never())
            ->method('send')
            ->willReturn(json_encode($responseData));

        return $restRpc;
    }
}
