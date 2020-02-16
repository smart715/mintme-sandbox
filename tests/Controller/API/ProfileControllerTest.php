<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class ProfileControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testValidateZipCode(): void
    {
        $this->register($this->client);

        $this->client->request('POST', '/api/profile/validate-zip-code', [
            'country' => 'EG',
        ]);
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($res['hasPattern']);
        $this->assertEquals('(\d\d\d\d\d)', $res['pattern']);

        $this->client->request('POST', '/api/profile/validate-zip-code', [
            'country' => '',
        ]);
        $res = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($res['hasPattern']);
        $this->assertEquals('', $res['pattern']);
    }
}
