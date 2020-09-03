<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;

class SummaryControllerTest extends WebTestCase
{
    public function testGetAssets(): void
    {
        $this->client->request('GET', '/dev/api/v2/open/summary');

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey(Token::MINTME_SYMBOL . '_' . Token::BTC_SYMBOL, $res[0]);
        $this->assertArrayHasKey(Token::MINTME_SYMBOL . '_' . Token::ETH_SYMBO, $res[1]);
    }
}
