<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class TradingControllerTest extends WebTestCase
{
    public function testTrading(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/trading/1');
        $res = $this->client->getResponse()->getContent();

        $this->assertContains(':page="1"', (string)$res);
        $this->assertRegExp('/:tokens-count="\\d+"/u', (string)$res);
    }
}
