<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Utils\LockFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Lock\LockInterface;

class SecurityControllerTest extends WebTestCase
{
    public function testLogin(): void
    {
        $fooClient = self::createClient();
        $randomEmail = $this->register($fooClient);

        $this->client->request('GET', self::LOCALHOST . '/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', self::LOCALHOST . '/login');
        $this->client->submitForm(
            '_submit',
            [
                '_username' => $randomEmail,
                '_password' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $user = $this->getUser($randomEmail);

        $lock = $this->fetchLock($user);

        $this->assertTrue($this->client->getResponse()->isRedirect('/login_check'));
        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isRedirect(self::LOCALHOST . '/profile'));
        $this->assertTrue(!$lock->acquire());
    }

    public function testLoginFails(): void
    {
        $fooClient = self::createClient();
        $randomEmail = $this->register($fooClient);

        $this->client->request('GET', self::LOCALHOST . '/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', self::LOCALHOST . '/login');
        $this->client->submitForm(
            '_submit',
            [
                '_username' => $randomEmail,
                '_password' => 'WrongPath123',
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertTrue($this->client->getResponse()->isRedirect('/login_check'));
        $this->client->followRedirect();

        $this->assertTrue($this->client->getResponse()->isRedirect(self::LOCALHOST . '/login'));
    }

    public function testRefererRedirect(): void
    {
        $randomEmail = $this->register($this->client);
        $tokName = $this->createToken($this->client);

        $fooClient = self::createClient();

        $fooClient->request('GET', self::LOCALHOST . '/token/' . $tokName . '/trade');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $fooClient->clickLink('Log In');
        $this->assertTrue($fooClient->getResponse()->isSuccessful());

        $fooClient->submitForm(
            '_submit',
            [
                '_username' => $randomEmail,
                '_password' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $user = $this->getUser($randomEmail);

        $lock = $this->fetchLock($user);

        $this->assertTrue($fooClient->getResponse()->isRedirect('/login_check'));
        $fooClient->followRedirect();

        $this->assertTrue($fooClient->getResponse()->isRedirect(self::LOCALHOST . '/login_success'));
        $fooClient->followRedirect();

        $this->assertTrue($fooClient->getResponse()->isRedirect(self::LOCALHOST . '/token/' . $tokName . '/trade'));
        $this->assertTrue($fooClient->getResponse()->isRedirection());
        $fooClient->followRedirect();

        $this->assertTrue($fooClient->getResponse()->isSuccessful());
        $this->assertStringContainsString($tokName, (string)$fooClient->getResponse()->getContent());

        $this->assertTrue(!$lock->acquire());
    }

    public function testGuestRedirectedToLoginPage(): void
    {
        /** @var LockFactory|MockObject $lock */
        $lock = $this->createMock(LockFactory::class);
        $fooClient = self::createClient();
        $fooClient->request('GET', self::LOCALHOST . '/login_success');

        $lock->expects($this->never())->method('createLock');
        $this->assertTrue($fooClient->getResponse()->isRedirect(self::LOCALHOST . '/trading'));
    }


    private function getUser(string $randomEmail): User
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $randomEmail]);
    }

    private function fetchLock(User $user): LockInterface
    {
        $lockFactory = new LockFactory($this->em);

        return $lockFactory->createLock(
            LockFactory::LOCK_WITHDRAW_AFTER_LOGIN . $user->getId(),
            60,
            false
        );
    }
}
