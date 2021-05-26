<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandler;
use App\Manager\TokenManager;
use App\Tests\Controller\WebTestCase;
use App\Utils\DateTime;
use App\Utils\Symbols;
use DateInterval;

class TokensControllerTest extends WebTestCase
{
    public function testUpdate(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('PATCH', '/api/tokens/' . $tokName, [
            'description' => str_repeat('a', 205),
            'facebookUrl' => 'www.facebook.com/foo',
            'telegramUrl' => 'https://t.me/joinchat/test',
            'discordUrl' => 'https://discordapp.com/invite/test',
            'youtubeChannelId' => 'foo-youtube-id',
        ]);

        /** @var Token $token */
        $token = $this->getToken($tokName);

        $this->assertEquals(
            [
                $token->getDescription(),
                $token->getFacebookUrl(),
                $token->getTelegramUrl(),
                $token->getDiscordUrl(),
                $token->getYoutubeChannelId(),
            ],
            [
                str_repeat('a', 205),
                'www.facebook.com/foo',
                'https://t.me/joinchat/test',
                'https://discordapp.com/invite/test',
                'foo-youtube-id',
            ]
        );
    }

    public function testSetTokenReleasePeriod(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/tokens/' . $tokName .'/lock-in', [
            'released' => '60',
            'releasePeriod' => 10,
        ]);

        /** @var Token $token */
        $token = $this->getToken($tokName);

        $this->assertEquals(
            [
                $token->getLockIn()->getFrozenAmount()->getAmount(),
                $token->getLockIn()->getReleasedAtStart()->getAmount(),
                $token->getLockIn()->getReleasePeriod(),
                $token->getLockIn()->getHourlyRate()->getAmount(),
            ],
            [
                '4000000000000000000',
                '6000000000000000000',
                '10',
                '45662100456621',
            ]
        );
    }

    public function testLockPeriod(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/tokens/' . $tokName .'/lock-in', [
            'released' => '60',
            'releasePeriod' => '10',
        ]);

        $this->client->request('GET', '/api/tokens/' . $tokName .'/lock-period');

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertEquals(
            [
                $res['releasePeriod'],
                $res['frozenAmount'],
                $res['hourlyRate'],
                $res['releasedAmount'],
            ],
            [
                10,
                '4000000.000000000000',
                '45.662100456621',
                '6000000.000000000000',
            ]
        );
    }

    /** @depends testUpdate() */
    public function testTokenSearch(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/tokens/search', [
            'tokenName' => $tokName,
        ]);

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $res);
        $this->assertEquals($tokName, $res[0]['name']);
    }

    public function testGetTokens(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/tokens');

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $res['common']);
        $this->assertCount(4, $res['predefined']);
        $this->assertArrayHasKey($tokName, $res['common']);
        $this->assertArrayHasKey('WEB', $res['predefined']);
        $this->assertArrayHasKey('BTC', $res['predefined']);
        $this->assertArrayHasKey('ETH', $res['predefined']);
        $this->assertArrayHasKey('USDC', $res['predefined']);
    }

    public function testGetTokenExchange(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/exchange-amount');

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertEquals('9999999.000000000000', $res);
    }

    public function testGetTokenWithdrawn(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        /** @var Token $token */
        $token = $this->getToken($tokName);
        $token->setWithdrawn('10000000000000');
        $token->setAddress('0x00');
        $this->em->persist($token);
        $this->em->flush();

        $this->client->request('GET', '/api/tokens/' . $tokName . '/withdrawn');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertEquals('10.000000000000', $res);
    }

    public function testIsTokenExchanged(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/is-exchanged');

        $this->assertFalse(
            json_decode((string)$this->client->getResponse()->getContent(), true)
        );

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/is-exchanged');

        $this->assertTrue(
            json_decode((string)$this->client->getResponse()->getContent(), true)
        );
    }

    public function testDelete(): void
    {
        $balanceHandler = self::$container->get(BalanceHandler::class);
        $tokenManager = self::$container->get(TokenManager::class);

        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);

        /** @var Token $token */
        $token = $this->getToken($tokName);

        $user = $token->getProfile()->getUser();

        $initBalance = $balanceHandler->balance(
            $user,
            $tokenManager->findByName(Symbols::WEB)
        );

        for ($i = 0; $i < 10; $i++) {
            $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
                'priceInput' => 1,
                'amountInput' => 10,
                'action' => 'buy',
            ]);
        }

        $user->setEmailAuthCode('123456');
        $codeExpirationTime = (new DateTime())->now()->add(new DateInterval('PT1M'));
        $user->setEmailAuthCodeExpirationTime($codeExpirationTime);
        $this->em->persist($user);
        $this->em->flush();

        $this->client->request('POST', '/api/tokens/' . $tokName . '/delete', [
            'name' => $tokName,
            'code' => '123456',
        ]);

        /** @var Token|null $token */
        $token = $this->getToken($tokName);
        $this->assertNull($token);

        $finalBalance = $balanceHandler->balance(
            $user,
            $tokenManager->findByName(Symbols::WEB)
        );

        $isSameBalance = $initBalance->getAvailable()->equals($finalBalance->getAvailable());
        $this->assertTrue($isSameBalance);
    }

    public function testSendCode(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        /** @var Token $token */
        $token = $this->getToken($tokName);
        $this->assertEquals('', $token->getProfile()->getUser()->getEmailAuthCode());

        $this->client->request('POST', '/api/tokens/' . $tokName . '/send-code');

        $this->em->clear();

        /** @var Token $token */
        $token = $this->getToken($tokName);
        $this->assertNotEquals('', $token->getProfile()->getUser()->getEmailAuthCode());
    }

    public function testGetTopHolders(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = static::createClient();
        $fooEmail = $this->register($fooClient);
        $this->sendWeb($fooEmail);

        $barClient = static::createClient();
        $barEmail = $this->register($barClient);
        $this->sendWeb($barEmail);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 4,
            'action' => 'sell',
        ]);

        $fooClient->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 2,
            'action' => 'buy',
        ]);

        $barClient->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'buy',
        ]);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/top-holders');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);


        $this->assertCount(2, $res);
        $this->assertEquals('1.996', $res[0]['balance']);
        $this->assertEquals('0.998', $res[1]['balance']);
    }

    public function testSoldOnMarket(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = static::createClient();
        $fooEmail = $this->register($fooClient);
        $this->sendWeb($fooEmail);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 4,
            'action' => 'sell',
        ]);

        $fooClient->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 2,
            'action' => 'buy',
        ]);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/sold');

        $this->assertEquals(
            '1.996000000000',
            json_decode((string)$this->client->getResponse()->getContent(), true)
        );
    }

    public function testCheckTokenNameExists(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);


        $this->client->request('GET', '/api/tokens/foo-token/check-token-name-exists');
        $this->assertFalse(
            json_decode((string)$this->client->getResponse()->getContent(), true)['exists']
        );

        $this->client->request('GET', '/api/tokens/' . $tokName . '/check-token-name-exists');
        $this->assertTrue(
            json_decode((string)$this->client->getResponse()->getContent(), true)['exists']
        );
    }

    public function testTokenDeployBalances(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/deploy');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('balance', $res);
        $this->assertArrayHasKey('webCost', $res);
        $this->assertEquals('100.000000000000000000', $res['balance']);
    }

    public function testDeployIfAlreadyDeployed(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        /** @var Token $token */
        $token = $this->getToken($tokName);
        $token->setAddress('0x');
        $this->em->persist($token);
        $this->em->flush();

        $this->client->request('POST', '/api/tokens/' . $tokName . '/deploy');

        $this->assertEquals($this->client->getResponse()->getStatusCode(), 400);
    }

    public function testDeployWithNoReleasePeriod(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/tokens/' . $tokName . '/deploy');

        $this->assertEquals($this->client->getResponse()->getStatusCode(), 400);
    }

    public function testDeployIfCantEdit(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = self::createClient();
        $this->register($fooClient);

        $fooClient->request('POST', '/api/tokens/' . $tokName . '/deploy');

        $this->assertEquals($fooClient->getResponse()->getStatusCode(), 400);
    }

    public function testDeploy(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);

        $this->client->request('POST', '/api/tokens/' . $tokName .'/lock-in', [
            'released' => '60',
            'releasePeriod' => '10',
        ]);

        $this->client->request('POST', '/api/tokens/' . $tokName . '/deploy');

        $this->em->clear();

        /** @var Token $token */
        $token = $this->getToken($tokName);

        $this->assertEquals('pending', $token->getDeploymentStatus());
    }

    public function testContractUpdateIfCantEdit(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = self::createClient();
        $this->register($fooClient);

        $fooClient->request('POST', '/api/tokens/' . $tokName . '/contract/update');

        $this->assertEquals($fooClient->getResponse()->getStatusCode(), 401);
    }

    public function testContractUpdateIfNotDeployed(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/tokens/' . $tokName . '/contract/update', [
            'address' => '0x00',
        ]);

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function testContractUpdate(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        /** @var Token $token */
        $token = $this->getToken($tokName);
        $token->setAddress('0x00');
        $this->em->persist($token);
        $this->em->flush();

        $this->client->request('POST', '/api/tokens/' . $tokName . '/contract/update', [
            'address' => '0x42e07422fa1bce2090912ddbc0717fa44654ef00',
        ]);

        $this->em->clear();

        /** @var Token $token */
        $token = $this->getToken($tokName);

        $this->assertEquals('0x', $token->getMintDestination());
    }

    private function getToken(string $name): ?Token
    {
        return $this->em->getRepository(Token::class)->findOneBy([
            'name' => $name,
        ]);
    }

    // todo test confirmWebsite()
}
