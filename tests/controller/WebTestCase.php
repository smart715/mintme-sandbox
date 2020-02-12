<?php declare(strict_types = 1);

namespace App\Tests\controller;

use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
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

    protected function nextUserId(): int
    {
        /** @var User[] $users */
        $users = $this->em->getRepository(User::class)
            ->findBy([], ['id' => 'DESC'], 1);

        return $users
            ? $users[0]->getId()
            : 1;
    }

    protected function register(Client $client): string
    {
        $email = $this->generateEmail($this->nextUserId());
        $client->request('GET', '/register/');
        $client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $this->generateEmail($this->nextUserId()),
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        return $email;
    }

    private function generateEmail(int $id): string
    {
        return sprintf('foo%s@mail.com', $id);
    }

    protected function truncateEntities(): void
    {
        (new ORMPurger($this->em))->purge();
    }
}
