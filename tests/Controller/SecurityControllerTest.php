<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class SecurityControllerTest extends WebTestCase
{
    public function testLogin(): void
    {
        $fooClient = self::createClient();
        $fooEmail = $this->register($fooClient);

        $this->client->request('GET', '/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', '/login');
        $this->client->submitForm(
            'Log In',
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

        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/profile'));
    }

    public function testLoginFails(): void
    {
        $fooClient = self::createClient();
        $fooEmail = $this->register($fooClient);

        $this->client->request('GET', '/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', '/login');
        $this->client->submitForm(
            'Log In',
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

        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/login'));
    }
}
