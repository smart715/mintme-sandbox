<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class CoinControllerTest extends WebTestCase
{
    public function testPair(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/coin/mintme/btc');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPairRedirectIfQuoteIsWEB(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/coin/web/btc');
        $this->assertTrue($this->client->getResponse()->isRedirect('/coin/MINTME/BTC'));
    }

    public function testPairIfNotFound(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/coin/btc/foo');
        $this->assertTrue($this->client->getResponse()->isNotFound());

        $this->client->request('GET', '/coin/foo/mintme');
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
