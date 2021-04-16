<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;
use App\Utils\Symbols;

class CryptosControllerTest extends WebTestCase
{
    public function testGetRates(): void
    {
        $this->register($this->client);

        $this->client->request('GET', self::LOCALHOST . '/api/cryptos/rates');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertCount(4, $res);
        $this->assertArrayHasKey('BTC', $res);
        $this->assertArrayHasKey('WEB', $res);
        $this->assertArrayHasKey('ETH', $res);
        $this->assertArrayHasKey('USDC', $res);
        $this->assertArrayHasKey('BTC', $res['BTC']);
        $this->assertArrayHasKey('USD', $res['BTC']);
        $this->assertArrayHasKey('ETH', $res['ETH']);
        $this->assertArrayHasKey('USD', $res['USDC']);
    }

    public function testGetBalance(): void
    {
        $email = $this->register($this->client);

        $this->sendWeb($email, '133000000000000000000');
        $this->client->request('GET', self::LOCALHOST . '/api/cryptos/WEB/balance');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('133.000000000000000000', $res);

        $this->deposit($email, '150000', Symbols::BTC);
        $this->client->request('GET', self::LOCALHOST . '/api/cryptos/BTC/balance');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('0.00150000', $res);
    }
}
