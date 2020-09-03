<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;

class OrderbookControllerTest extends WebTestCase
{
    public function testGetOrderbook($baseQuote): void
    {
        $markets = [
            Token::MINTME_SYMBOL . '_' . Token::BTC_SYMBOL,
            Token::MINTME_SYMBOL . '_' . Token::ETH_SYMBOL,
        ];

        foreach($markets as $market) {

            $this->client->request(
                'GET',
                "/dev/api/v2/open/orderbook/$market",
                [
                    'depth' => 20,
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

    public function testRebrandingRedirect(): void
    {
        $this->client->request(
            'GET',
            '/dev/api/v2/open/orderbook/' . Token::WEB_SYMBOL . '_' . Token::BTC_SYMBOL
        );
        $this->assertTrue($this->client->getResponse()->isRedirect(
                '/dev/api/v2/open/orderbook/' . Token::MINTME_SYMBOL . '_' . Token::BTC_SYMBOL
            )
        );
        $this->client->request(
            'GET',
            '/dev/api/v2/open/orderbook/' . Token::WEB_SYMBOL . '_' . Token::ETH_SYMBOL
        );
        $this->assertTrue($this->client->getResponse()->isRedirect(
                '/dev/api/v2/open/orderbook/' . Token::MINTME_SYMBOL . '_' . Token::ETH_SYMBOL
            )
        );
    }
}
