<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use Symfony\Bundle\FrameworkBundle\Client;

class MarketsControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testGetMarkets(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/markets');

        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $res);
        $this->assertEquals(
            [
                $res[0]['base']['symbol'],
                $res[0]['quote']['symbol'],
                $res[0]['identifier'],
                $res[1]['base']['symbol'],
                $res[1]['quote']['symbol'],
            ],
            [
                'BTC',
                'WEB',
                'WEBBTC',
                'WEB',
                $tokName,
            ]
        );
    }

    /** @depends testGetMarkets */
    public function testGetMarketsInfo(): void
    {
        $this->register($this->client);

        $this->client->request('GET', '/api/markets/info/1');

        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThan(1, $res['markets']);
    }

    // todo test getMarketKline()

    public function testGetMarketCap(): void
    {
        $this->register($this->client);

        $this->client->request('GET', '/api/markets/marketcap/WEB');

        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('marketcap', $res);
    }
}
