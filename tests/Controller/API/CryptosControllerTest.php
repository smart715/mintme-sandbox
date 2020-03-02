<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;

class CryptosControllerTest extends WebTestCase
{
    public function testGetRates(): void
    {
        $this->register($this->client);

        $this->client->request('GET', '/api/cryptos/rates');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $res);
        $this->assertArrayHasKey('BTC', $res);
        $this->assertArrayHasKey('WEB', $res);
        $this->assertArrayHasKey('BTC', $res['BTC']);
        $this->assertArrayHasKey('USD', $res['BTC']);
    }
}
