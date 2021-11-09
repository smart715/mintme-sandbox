<?php declare(strict_types = 1);

namespace App\Tests\Controller;

class DefaultControllerTest extends WebTestCase
{
    /** @dataProvider unAuthUPages */
    public function testUnauthorizedPages(string $route): void
    {
        $this->client->request('GET', self::LOCALHOST . $route);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertGreaterThan(
            0,
            $this->client->getCrawler()->filter('nav ul li a[href="/login"]')->count()
        );
    }

    /** @dataProvider authPages */
    public function testAuthorizedPages(string $route): void
    {
        $this->client->request('GET', self::LOCALHOST . $route);
        $this->assertTrue($this->client->getResponse()->isRedirect(self::LOCALHOST . '/login'));

        $this->register($this->client);

        $this->client->request('GET', $route);
        $this->assertFalse($this->client->getResponse()->isRedirect());
    }

    public function testProfileAuth(): void
    {
        $this->client->request('GET', self::LOCALHOST . '/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect(self::LOCALHOST .'/login'));

        $nickname = $this->generateString();
        $this->register($this->client, $nickname);

        $this->client->request('GET', '/profile');

        $this->assertTrue($this->client->getResponse()->isRedirect('/profile/' . $nickname));
    }

    public function unAuthUPages(): array
    {
        return [
            [''],
            ['/trading'],
            ['/news'],
            ['/kb'],
            ['/register/'],
            ['/login'],
            ['/dev/documentation/v1'],
            ['/privacy-policy'],
            ['/terms-of-service'],
        ];
    }

    public function authPages(): array
    {
        return [
            ['/token'],
            ['/chat'],
            ['/wallet'],
        ];
    }
}
