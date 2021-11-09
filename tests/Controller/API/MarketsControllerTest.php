<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;

class MarketsControllerTest extends WebTestCase
{
    public function testGetMarkets(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/api/markets');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        file_put_contents('test.json', json_encode($res));

        $this->assertCount(4, $res);
        $this->assertEquals(
            [
                $res[0]['base']['symbol'],
                $res[0]['quote']['symbol'],
                $res[0]['identifier'],
                $res[1]['base']['symbol'],
                $res[1]['quote']['symbol'],
                $res[1]['identifier'],
                $res[2]['base']['symbol'],
                $res[2]['quote']['symbol'],
                $res[2]['identifier'],
                $res[3]['base']['symbol'],
                $res[3]['quote']['symbol'],
            ],
            [
                'BTC',
                'WEB',
                'WEBBTC',
                'ETH',
                'WEB',
                'WEBETH',
                'USDC',
                'WEB',
                'WEBUSDC',
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

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertGreaterThan(4, $res['markets']);
    }

    // todo test getMarketKline()

    public function testGetMarketCap(): void
    {
        $this->register($this->client);

        $this->client->request('GET', '/api/markets/marketcap/WEB');

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('marketcap', $res);
    }
}
