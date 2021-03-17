<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;
use App\Utils\Symbols;

class AssetsControllerTest extends WebTestCase
{
    public function testGetAssets(): void
    {
        $this->client->request('GET', '/dev/api/v2/open/assets');

        $this->client->followRedirect();

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey(Symbols::MINTME, $res);
        $this->assertArrayHasKey(Symbols::BTC, $res);
        $this->assertArrayHasKey(Symbols::ETH, $res);
    }
}
