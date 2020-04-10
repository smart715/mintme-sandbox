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

    public function testLoginFromTab(): void
    {
        $fooEmail = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/logout');
        $this->client->followRedirect();

        $this->client->request('GET', '/token/' . $tokName . '/donation');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->request('GET', '/login?formContentOnly=true');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

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

    public function testRefererRedirect(): void
    {
        $userEmail = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);

        $this->client->request('GET', '/logout');
        $this->client->followRedirect();

        $this->client->request('GET', '/token/' . $tokName . '/trade');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->clickLink('Log In');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->submitForm(
            'Log In',
            [
                '_username' => $userEmail,
                '_password' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect('/login_check'));
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/login_success'));
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/token/' . $tokName . '/trade'));
        $this->assertTrue($this->client->getResponse()->isRedirection());
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains($tokName, $this->client->getResponse()->getContent());
    }
}
