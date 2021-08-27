<?php declare(strict_types = 1);

namespace App\Tests\Controller\Dev\API\V2;

use App\Tests\Controller\WebTestCase;

class SummaryControllerTest extends WebTestCase
{
    public function testGetAssets(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/dev/api/v2/open/summary');

        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->followRedirect();

        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(2, count($res));
    }
}
