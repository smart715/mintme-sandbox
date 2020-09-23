<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\Token\Token;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

class WebTestCase extends BaseWebTestCase
{
    protected const DEFAULT_USER_PASS = 'Foo123456';

    /** @var EntityManagerInterface */
    protected $em;

    /** @var Client */
    protected $client;

    public function setUp(): void
    {
        self::bootKernel();

        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->client = static::createClient();
        $_SERVER['HTTP_USER_AGENT'] = 'foo/1';
    }

    protected function register(Client $client, string $nickname = ''): string
    {
        $email = $this->generateEmail();

        $client->request('GET', '/register/');
        $client->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $email,
                'fos_user_registration_form[nickname]' => $nickname ?: $this->generateString(),
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

    protected function sendWeb(string $email, string $amount = '100000000000000000000'): void
    {
        $this->deposit(
            $email,
            $amount,
            Token::WEB_SYMBOL
        );
    }

    protected function deposit(
        string $email,
        string $amount = '100000000000000000000',
        string $currency = Token::WEB_SYMBOL
    ): void {
        $balanceHandler = self::$container->get('balancer');

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $balanceHandler->deposit(
            $user,
            Token::getFromSymbol($currency),
            new Money($amount, new Currency($currency))
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

    protected function generateString(int $len = 30): string
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

    /**
     * @param string[] $entities
     */
    protected function truncateEntities(array $entities): void
    {
        $connection = $this->em->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();

        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->em->getClassMetadata($entity)->getTableName()
            );
            $connection->executeUpdate($query);
        }

        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
