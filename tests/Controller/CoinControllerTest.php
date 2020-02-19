<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;

class CoinControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testPair(): void
    {
        $this->client->request('GET', '/coin/btc/mintme');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testPairRedirectIfQuoteIsWEB(): void
    {
        $this->client->request('GET', '/coin/btc/web');
        $this->assertTrue($this->client->getResponse()->isRedirect('/coin/BTC/MINTME'));
    }

    public function testPairIfNotFound(): void
    {
        $this->client->request('GET', '/coin/btc/foo');
        $this->assertTrue($this->client->getResponse()->isNotFound());

        $this->client->request('GET', '/coin/foo/mintme');
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
