<?php declare(strict_types = 1);

namespace App\Tests\Controller;

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

    public function testCreatingProfile(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }
}
