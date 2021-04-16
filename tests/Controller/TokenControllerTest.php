<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class TokenControllerTest extends WebTestCase
{
    public function testCreatingToken(): void
    {
        $this->register($this->client);

        $this->client->request('GET', '/token');
        $this->assertFalse($this->client->getResponse()->isRedirect());

        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/token');
        $this->assertTrue($this->client->getResponse()->isRedirect('/token/' . $tokName));
    }

    public function testShowTab(): void
    {
        $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/token/fake');
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertTrue($this->client->getResponse()->isNotFound());

        $this->client->request('GET', '/token/' . $tokName . '/intro');
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $this->client->request('GET', '/token/' . $tokName . '/trade');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/token/' . $tokName . '/test');
        $this->assertTrue($this->client->getResponse()->isClientError());
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
