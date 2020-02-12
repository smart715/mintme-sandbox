<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

class WebTestCase extends BaseWebTestCase
{
    protected const DEFAULT_USER_PASS = 'Foo123456';

    /** @var EntityManagerInterface */
    private $em;

    public function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
    }

    protected function lastUserId(): int
    {
        /** @var User[] $users */
        $users = $this->em->getRepository(User::class)
            ->findBy([], ['id' => 'DESC'], 1);

        return $users
            ? $users[0]->getId()
            : 0;
    }

    protected function register(Client $client): void
    {
        $client->request('GET', '/register/');
        $client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $this->generateEmail(),
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );
    }

    protected function createProfile(
        Client $client,
        string $fName = 'foo',
        string $lName = 'bar'
    ): void {
        $client->request('GET', '/profile');

        $client->submitForm(
            'Save',
            [
                'profile[firstName]' => $fName,
                'profile[lastName]' => $lName,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );
    }

    protected function createToken(Client $client): void
    {
        $client->request('GET', '/token');

        $client->submitForm(
            'Create token',
            [
                'token_create[name]' => 'tok'. $this->generateString(),
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );
    }

    protected function generateString(int $len = 12): string
    {
        $chars = range('a', 'z');
        $charsCount = count($chars);
        $str = '';

        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[random_int(0, $charsCount - 1)];
        }

        return $str;
    }

    private function generateEmail(): string
    {
        return sprintf('foo%s@mail.com', $this->generateString());
    }
}
