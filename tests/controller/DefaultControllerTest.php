<?php declare(strict_types = 1);

namespace App\Tests\controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    /** @dataProvider provideUrls */
    public function testPagesWithoutLogin(string $url): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertGreaterThan(
            0,
            $client->getCrawler()->filter('nav ul li a[href="/login"]')->count()
        );
    }

    public function provideUrls(): array
    {
        return [
            ['/'],
            ['/trading'],
            ['/news/archive'],
            ['/kb'],
            ['/register'],
            ['/login'],
            ['/dev/documentation/v1'],
            ['/privacy-policy'],
            ['/terms-of-service'],
        ];
    }
}