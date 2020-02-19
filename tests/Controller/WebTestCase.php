<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

class WebTestCase extends BaseWebTestCase
{
    protected const DEFAULT_USER_PASS = 'Foo123456';

    /** @var ObjectManager */
    protected $em;

    public function setUp(): void
    {
        self::bootKernel();

        $this->em = self::$container->get('doctrine')->getManager();
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

    protected function register(Client $client): string
    {
        $email = $this->generateEmail();

        $client->request('GET', '/register/');
        $client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $email,
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        return $email;
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

    /** @test */
    protected function sendWeb(string $email, string $amount = '100000000000000000000'): void
    {
        $balanceHandler = self::$container->get(BalanceHandlerInterface::class);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $balanceHandler->deposit(
            $user,
            Token::getFromSymbol(Token::WEB_SYMBOL),
            new Money($amount, new Currency(Token::WEB_SYMBOL))
        );
    }

    protected function createToken(Client $client): string
    {
        $name = $this->generateString();

        $client->request('GET', '/token');

        $client->submitForm(
            'Create token',
            [
                'token_create[name]' => $name,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        return $name;
    }

    protected function generateString(int $len = 20): string
    {
        $chars = range('a', 'z');
        $charsCount = count($chars);
        $str = '';

        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[random_int(0, $charsCount - 1)];
        }

        return $str;
    }

    protected function generateEmail(): string
    {
        return $this->generateString() . '@mail.com';
    }
}
