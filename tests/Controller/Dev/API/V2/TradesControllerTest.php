<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;

class TradesControllerTest extends WebTestCase
{
    private const URL = '/dev/api/v2/open/trades';

    public function testGetTrades(): void
    {
        $markets = [
            Token::MINTME_SYMBOL . '_' . Token::BTC_SYMBOL,
            Token::MINTME_SYMBOL . '_' . Token::ETH_SYMBOL,
        ];

        foreach ($markets as $market) {
            $this->client->request(
                'GET',
                self::URL . '/' . $market
            );

            $this->assertTrue($this->client->getResponse()->isSuccessful());
        }
    }
}
