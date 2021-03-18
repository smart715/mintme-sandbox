<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;
use App\Utils\Symbols;

class OrderbookControllerTest extends WebTestCase
{
    private const URL = '/dev/api/v2/open/orderbook';

    public function testGetOrderbook(): void
    {
        $markets = [
            Symbols::MINTME . '_' . Symbols::BTC,
            Symbols::MINTME . '_' . Symbols::ETH,
        ];

        foreach ($markets as $market) {
            $this->client->request(
                'GET',
                self::URL . '/' . $market,
                [
                    'depth' => 5,
                    'level' => 3,
                ]
            );

            $this->assertTrue($this->client->getResponse()->isSuccessful());

            $res = json_decode((string)$this->client->getResponse()->getContent(), true);

            $this->assertCount(3, $res);
            $this->assertArrayHasKey('asks', $res);
            $this->assertArrayHasKey('bids', $res);
            $this->assertArrayHasKey('timestamp', $res);
        }
    }
}
