<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;
use App\Utils\DateTime;
use DateInterval;
use Symfony\Bundle\FrameworkBundle\Client;

class TokensControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testUpdate(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('PATCH', '/api/tokens/' . $tokName, [
            'name' => $tokName . '-foo',
            'description' => 'description for token',
            'facebookUrl' => 'www.facebook.com/foo',
            'telegramUrl' => 'www.telegram.com',
            'discordUrl' => 'www.discord.com',
            'youtubeChannelId' => 'foo-youtube-id',
        ]);

        /** @var Token $token */
        $token = $this->getToken($tokName . '-foo');

        $this->assertEquals(
            [
                $token->getName(),
                $token->getDescription(),
                $token->getFacebookUrl(),
                $token->getTelegramUrl(),
                $token->getDiscordUrl(),
                $token->getYoutubeChannelId(),
            ],
            [
                $tokName . '-foo',
                'description for token',
                'www.facebook.com/foo',
                'http://www.telegram.com',
                'http://www.discord.com',
                'foo-youtube-id',
            ]
        );
    }

    public function testSetTokenReleasePeriod(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/tokens/' . $tokName .'/lock-in', [
            'released' => '60',
            'releasePeriod' => '10',
        ]);

        /** @var Token $token */
        $token = $this->getToken($tokName);

        $this->assertEquals(
            [
                $token->getLockIn()->getFrozenAmount()->getAmount(),
                $token->getLockIn()->getReleasedAtStart(),
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
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/tokens/' . $tokName .'/lock-in', [
            'released' => '60',
            'releasePeriod' => '10',
        ]);

        $this->client->request('GET', '/api/tokens/' . $tokName .'/lock-period');

        $res = json_decode($this->client->getResponse()->getContent(), true);

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
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/tokens/search', [
            'tokenName' => $tokName,
        ]);

        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $res);
        $this->assertEquals($tokName, $res[0]['name']);
    }

    public function testGetTokens(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/tokens');

        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $res['common']);
        $this->assertCount(2, $res['predefined']);
        $this->assertArrayHasKey($tokName, $res['common']);
        $this->assertArrayHasKey('WEB', $res['predefined']);
        $this->assertArrayHasKey('BTC', $res['predefined']);
    }

    public function testGetTokenExchange(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/exchange-amount');

        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('9999999.000000000000', $res);
    }

    public function testGetTokenWithdrawn(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $token = $this->getToken($tokName);
        $token->setWithdrawn('10000000000000');
        $token->setAddress('0x00');
        $this->em->persist($token);
        $this->em->flush();

        $this->client->request('GET', '/api/tokens/' . $tokName . '/withdrawn');
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('10.000000000000', $res);
    }

    public function testIsTokenExchanged(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/is-exchanged');

        $this->assertFalse(
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->client->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 1,
            'action' => 'sell',
        ]);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/is-exchanged');

        $this->assertTrue(
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testIsTokenNotDeployed(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/tokens/' . $tokName . '/is-not_deployed');

        $this->assertTrue(
            json_decode($this->client->getResponse()->getContent(), true)
        );


        $token = $this->getToken($tokName);
        $token->setAddress('0x00');
        $this->em->persist($token);
        $this->em->flush();

        $this->client->request('GET', '/api/tokens/' . $tokName . '/is-not_deployed');

        $this->assertFalse(
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testDelete(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        /** @var Token $tokem */
        $token = $this->getToken($tokName);

        $user = $token->getProfile()->getUser();
        $user->setEmailAuthCode('123456');
        $codeExpirationTime = (new DateTime())->now()->add(new DateInterval('PT1M'));
        $user->setEmailAuthCodeExpirationTime($codeExpirationTime);
        $this->em->persist($user);
        $this->em->flush();

        $this->client->request('POST', '/api/tokens/' . $tokName . '/delete', [
            'name' => $tokName,
            'code' => '123456',
        ]);

        /** @var Token $tokem */
        $token = $this->getToken($tokName);

        $this->assertNull($token);
    }

    public function testSendCode(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        /** @var Token $token */
        $token = $this->getToken($tokName);
        $this->assertEquals('', $token->getProfile()->getUser()->getEmailAuthCode());

        $this->client->request('POST', '/api/tokens/' . $tokName . '/send-code');

        /** @var Token */
        $token = $this->getToken($tokName);
//        $this->assertNotEquals('', $token->getProfile()->getUser()->getEmailAuthCode());
    }

    public function testGetTopHolders(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = static::createClient();
        $fooEmail = $this->register($fooClient);
        $this->createProfile($fooClient);
        $this->sendWeb($fooEmail);

        $barClient = static::createClient();
        $barEmail = $this->register($barClient);
        $this->createProfile($barClient);
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
        $res = json_decode($this->client->getResponse()->getContent(), true);


        $this->assertCount(2, $res);
        $this->assertEquals('1.996', $res[0]['balance']);
        $this->assertEquals('0.998', $res[1]['balance']);
    }

    public function testSoldOnMarket(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = static::createClient();
        $fooEmail = $this->register($fooClient);
        $this->createProfile($fooClient);
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
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testCheckTokenNameExists(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);


        $this->client->request('GET', '/api/tokens/foo-token/check-token-name-exists');
        $this->assertFalse(
            json_decode($this->client->getResponse()->getContent(), true)['exists']
        );

        $this->client->request('GET', '/api/tokens/' . $tokName . '/check-token-name-exists');
        $this->assertTrue(
            json_decode($this->client->getResponse()->getContent(), true)['exists']
        );
    }

    private function getToken(string $name): ?Token
    {
        return $this->em->getRepository(Token::class)->findOneBy([
            'name' => $name,
        ]);
    }

    // todo fix testSendCode
    // todo test confirmWebsite()
    // todo test tokenDeployBalances()
    // todo test deploy()
    // todo test contractUpdate()
}
