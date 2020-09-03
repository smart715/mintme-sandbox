<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Tests\Controller\WebTestCase;

class AssetsControllerTest extends WebTestCase
{
    public function testGetAssets(): void
    {
        $this->client->request('GET', '/dev/api/v2/open/assets');

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('MINTME', $res);
        $this->assertArrayHasKey('BTC', $res);
        $this->assertArrayHasKey('ETH', $res);
    }
}
