<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;

class AssetsControllerTest extends WebTestCase
{
    public function testGetAssets(): void
    {
        $this->client->request('GET', '/dev/api/v2/open/assets');

        $this->client->followRedirect();

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey(Token::MINTME_SYMBOL, $res);
        $this->assertArrayHasKey(Token::BTC_SYMBOL, $res);
        $this->assertArrayHasKey(Token::ETH_SYMBOL, $res);
    }
}
