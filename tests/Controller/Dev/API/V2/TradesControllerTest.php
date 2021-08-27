<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;
use App\Utils\Symbols;

class TradesControllerTest extends WebTestCase
{
    private const URL = self::LOCALHOST . '/dev/api/v2/open/trades';

    public function testGetTrades(): void
    {
        $markets = [
            Symbols::MINTME . '_' . Symbols::BTC,
            Symbols::MINTME . '_' . Symbols::ETH,
            Symbols::MINTME . '_' . Symbols::USDC,
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
