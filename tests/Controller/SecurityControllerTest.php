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

        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/profile'));
    }

    public function testLoginFromTab(): void
    {
        $fooEmail = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = self::createClient();

        $fooClient->request('GET', '/token/' . $tokName . '/buy');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $fooClient->request('GET', '/login?formContentOnly=true');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $fooClient->submitForm(
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

        $this->assertTrue($fooClient->getResponse()->isRedirect('/login_check'));
        $fooClient->followRedirect();
    }

    public function testLoginFails(): void
    {
        $fooClient = self::createClient();
        $fooEmail = $this->register($fooClient);

        $this->client->request('GET', '/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', '/login');
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

        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/login'));
    }

    public function testRefererRedirect(): void
    {
        $userEmail = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = self::createClient();

        $fooClient->request('GET', '/token/' . $tokName . '/trade');
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
        $this->assertTrue($fooClient->getResponse()->isRedirect('http://localhost/login_success'));
        $fooClient->followRedirect();
        $this->assertTrue($fooClient->getResponse()->isRedirect('http://localhost/token/' . $tokName . '/trade'));
        $this->assertTrue($fooClient->getResponse()->isRedirection());
        $fooClient->followRedirect();
        $this->assertTrue($fooClient->getResponse()->isSuccessful());
        $this->assertStringContainsString($tokName, (string)$fooClient->getResponse()->getContent());
    }
}
