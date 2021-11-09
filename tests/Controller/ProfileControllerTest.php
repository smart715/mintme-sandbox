<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class ProfileControllerTest extends WebTestCase
{
    public function testCreatingProfile(): void
    {
        $this->register($this->client);

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }
}
