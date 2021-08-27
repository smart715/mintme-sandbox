<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Entity\Token\Token;
use App\Tests\Controller\WebTestCase;

class TickerControllerTest extends WebTestCase
{
    public function testGetAssets(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/dev/api/v2/open/ticker');

        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(2, count($res));
    }
}
