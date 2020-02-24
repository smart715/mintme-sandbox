<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;

class TradingControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testTrading(): void
    {
        $this->client->request('GET', '/trading/1');
        $res = $this->client->getResponse()->getContent();

        file_put_contents('test.html', $this->client->getResponse()->getContent());

        $this->assertContains(':page="1"', $res);
        $this->assertRegExp('/:tokens-count="\\d+"/u', $res);
    }
}
