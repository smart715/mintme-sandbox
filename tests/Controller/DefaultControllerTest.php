<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class DefaultControllerTest extends WebTestCase
{
    /** @dataProvider unAuthUPages */
    public function testUnauthorizedPages(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertGreaterThan(
            0,
            $this->client->getCrawler()->filter('nav ul li a[href="/login"]')->count()
        );
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
            ['/news'],
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
