<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;

class DefaultControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    /** @dataProvider unAuthUPages */
    public function testUnauthorizedPages(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertGreaterThan(
            0,
            $this->client->getCrawler()->filter('nav ul li a[href="/login"]')->count()
        );
        $this->assertTrue(true);
    }

    /** @dataProvider authPages */
    public function testAuthorizedPages(string $url): void
    {
        $this->client->request('GET', $url);
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/login'));

        $this->register($this->client);

        $this->client->request('GET', $url);
        $this->assertFalse($this->client->getResponse()->isRedirect());
    }

    public function unAuthUPages(): array
    {
        return [
            ['/'],
            ['/trading'],
            ['/news/archive'],
            ['/kb'],
            ['/register/'],
            ['/login'],
            ['/dev/documentation/v1/'],
            ['/privacy-policy'],
            ['/terms-of-service'],
        ];
    }

    public function authPages(): array
    {
        return [
            ['/profile'],
            ['/token'],
        ];
    }
}
