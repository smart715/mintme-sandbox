<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class SecurityControllerTest extends WebTestCase
{
    public function testLogin(): void
    {
        $fooClient = self::createClient();
        $fooEmail = $this->register($fooClient);

        $this->client->request('GET', self::LOCALHOST . '/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', self::LOCALHOST . '/login');
        $this->client->submitForm(
            '_submit',
            [
                '_username' => $fooEmail,
                '_password' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect('/login_check'));
        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isRedirect(self::LOCALHOST . '/profile'));
    }

    public function testLoginFails(): void
    {
        $fooClient = self::createClient();
        $fooEmail = $this->register($fooClient);

        $this->client->request('GET', self::LOCALHOST . '/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', self::LOCALHOST . '/login');
        $this->client->submitForm(
            '_submit',
            [
                '_username' => $fooEmail,
                '_password' => 'WrongPath123',
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect('/login_check'));
        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isRedirect(self::LOCALHOST . '/login'));
    }

    public function testRefererRedirect(): void
    {
        $userEmail = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = self::createClient();

        $fooClient->request('GET', self::LOCALHOST . '/token/' . $tokName . '/trade');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $fooClient->clickLink('Log In');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $fooClient->submitForm(
            '_submit',
            [
                '_username' => $userEmail,
                '_password' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertTrue($fooClient->getResponse()->isRedirect('/login_check'));
        $fooClient->followRedirect();
        $this->assertTrue($fooClient->getResponse()->isRedirect(self::LOCALHOST . '/login_success'));
        $fooClient->followRedirect();
        $this->assertTrue($fooClient->getResponse()->isRedirect(self::LOCALHOST . '/token/' . $tokName . '/trade'));
        $this->assertTrue($fooClient->getResponse()->isRedirection());
        $fooClient->followRedirect();
        $this->assertTrue($fooClient->getResponse()->isSuccessful());
        $this->assertStringContainsString($tokName, (string)$fooClient->getResponse()->getContent());
    }
}
