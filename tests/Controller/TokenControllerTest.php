<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class TokenControllerTest extends WebTestCase
{
    public function testCreatingToken(): void
    {
        $this->register($this->client);
        $this->createProfile($this->client);

        $this->client->request('GET', '/token');
        $this->assertFalse($this->client->getResponse()->isRedirect());

        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/token');
        $this->assertTrue($this->client->getResponse()->isRedirect('/token/' . $tokName));
    }
}
