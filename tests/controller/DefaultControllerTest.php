<?php declare(strict_types = 1);

namespace App\Tests\controller;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    /** @var EntityManagerInterface */
    private $em;

    public function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $this->truncateEntities();
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

        $this->register('foo@mail.com', 'Foo123456');

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

    private function register(string $email, string $pass): void
    {

        $this->client->request('GET', '/register/');
        $this->client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $email,
                'fos_user_registration_form[plainPassword]' => $pass,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );
    }

    private function truncateEntities(): void
    {
        (new ORMPurger($this->em))->purge();
    }
}
