<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\User;
use App\Manager\TwoFactorManagerInterface;
use Symfony\Bundle\FrameworkBundle\Client;

class SecurityControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

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
}
