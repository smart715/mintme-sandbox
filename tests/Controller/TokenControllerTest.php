<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;

class TokenControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testCreatingToken(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);

        $this->client->request('GET', '/token');
        $this->assertFalse($this->client->getResponse()->isRedirect());

        $this->client->submitForm(
            'Create token',
            [
                'token_create[name]' => 'tok'. $this->generateString(),
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );
        $this->client->request('GET', '/token');
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }
}
