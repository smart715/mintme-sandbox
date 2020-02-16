<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Tests\Controller\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class LoggerControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testLog(): void
    {
        $this->register($this->client);

        $this->client->request('POST', '/api/logs/', [
            'level' => 'info',
            'message' => 'test logger',
        ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testLogWithNotExistLevel(): void
    {
        $this->register($this->client);

        $this->client->request('POST', '/api/logs/', [
            'level' => 'foo',
            'message' => 'test logger',
        ]);
        $this->assertFalse($this->client->getResponse()->isSuccessful());
    }
}
