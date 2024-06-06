<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManager;
use App\Repository\UserChangeEmailRequestRepository;
use App\Repository\UserCryptoRepository;
use App\Repository\UserRepository;
use App\Repository\UserTokenRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testFind(): void
    {
        $user = $this->mockUser();
        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager(
                $this->mockRepository($user)
            ),
            'Foo',
            'test',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->createMock(UserChangeEmailRequestRepository::class),
            $this->createMock(UserTokenRepository::class),
            $this->createMock(UserCryptoRepository::class)
        );

        $this->assertEquals($user, $manager->find(1));
    }

    public function testFindByReferralCode(): void
    {
        $user = $this->mockUser();
        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager(
                $this->mockRepository($user)
            ),
            'Foo',
            'test',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->createMock(UserChangeEmailRequestRepository::class),
            $this->createMock(UserTokenRepository::class),
            $this->createMock(UserCryptoRepository::class)
        );

        $this->assertEquals($user, $manager->findByReferralCode('foo'));
    }

    public function testFindByDiscordId(): void
    {
        $user = $this->mockUser();

        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager(
                $this->mockRepository($user)
            ),
            'Foo',
            'test',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->createMock(UserChangeEmailRequestRepository::class),
            $this->createMock(UserTokenRepository::class),
            $this->createMock(UserCryptoRepository::class)
        );

        $this->assertEquals($user, $manager->findByDiscordId(1));
    }

    public function testFindByDomain(): void
    {
        $domain = 'TEST_DOMAIN';
        $user = $this->mockUser();

        $repository = $this->mockRepository($user);
        $repository
            ->expects($this->once())
            ->method('findByDomain')
            ->with($domain)
            ->willReturn([]);

        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager(
                $repository
            ),
            'Foo',
            'test',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->createMock(UserChangeEmailRequestRepository::class),
            $this->createMock(UserTokenRepository::class),
            $this->createMock(UserCryptoRepository::class)
        );

        $this->assertEquals([], $manager->findByDomain($domain));
    }

    public function testFindUserByEmail(): void
    {
        $email = 'TEST_EMAIL';
        $user = $this->mockUser();

        $repository = $this->mockRepository($user);

        $om = $this->mockObjectManager($repository);

        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $om,
            'Foo',
            'test',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->createMock(UserChangeEmailRequestRepository::class),
            $this->createMock(UserTokenRepository::class),
            $this->createMock(UserCryptoRepository::class)
        );

        $this->assertEquals($user, $manager->findUserByEmail($email));
    }

    public function testCheckExistCanonicalEmail(): void
    {
        $email = 'TEST_EMAIL';
        $user = $this->mockUser();
        $emailExists = true;

        $repository = $this->mockRepository($user);
        $repository
            ->expects($this->once())
            ->method('checkExistCanonicalEmail')
            ->with($email)
            ->willReturn($emailExists);

        $om = $this->mockObjectManager($repository);

        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $om,
            'Foo',
            'test',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->createMock(UserChangeEmailRequestRepository::class),
            $this->createMock(UserTokenRepository::class),
            $this->createMock(UserCryptoRepository::class)
        );

        $this->assertEquals($emailExists, $manager->checkExistCanonicalEmail($email));
    }

    public function testGetUserToken(): void
    {
        $userTokens = ['TEST_USERTOKENS'];
        $userIds = [1,2];
        $token = $this->mockToken();

        $repository = $this->mockRepository(null);

        $om = $this->mockObjectManager($repository);

        $userTokenRepository = $this->mockUserTokenRepository();
        $userTokenRepository
            ->expects($this->once())
            ->method('getUserToken')
            ->with($token, $userIds)
            ->willReturn($userTokens);

        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $om,
            'Foo',
            'test',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->createMock(UserChangeEmailRequestRepository::class),
            $userTokenRepository,
            $this->createMock(UserCryptoRepository::class)
        );

        $this->assertEquals($userTokens, $manager->getUserToken($token, $userIds));
    }

    /**
     * @dataProvider sentMintmeExchangeMailDataProvider
     */
    public function testSentMintmeExchangeMail(array $cryptoData, string $expectedCryptosList): void
    {
        $user = $this->mockUser();
        $cryptos = [];
        $exchangeCryptos = [];

        foreach ($cryptoData as $item) {
            $crypto = $this->mockCrypto();
            $crypto->method('getSymbol')->willReturn($item['symbol']);
            $crypto->method('isTradable')->willReturn($item['isTradable']);
            array_push($cryptos, $crypto);

            !$item['isExchangeCrypto']?:array_push($exchangeCryptos, $crypto);
        }

        $cryptoManager = $this->mockCryptoManagerInterface();
        $cryptoManager
            ->method('findAll')
            ->willReturn($cryptos);

        $mailer = $this->mockMailerInterface();
        $mailer
            ->method('sentMintmeExchangeMail')
            ->with($user, $exchangeCryptos, $expectedCryptosList);

        $entityManager = $this->mockEntityManagerInterface();
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $entityManager
            ->expects($this->once())
            ->method('flush');


        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager(
                $this->mockRepository($user)
            ),
            'Foo',
            'test',
            $cryptoManager,
            $mailer,
            $entityManager,
            $this->createMock(UserChangeEmailRequestRepository::class),
            $this->createMock(UserTokenRepository::class),
            $this->createMock(UserCryptoRepository::class)
        );

        $manager->sendMintmeExchangeMail($user);
    }

    public function sentMintmeExchangeMailDataProvider(): array
    {
        return [
            'All three cryptos sent in mintme exchange mail' => [
                'cryptoData' => [
                    ['symbol' => 'TEST1', 'isTradable' => true, 'isExchangeCrypto' => true],
                    ['symbol' => 'TEST2', 'isTradable' => true, 'isExchangeCrypto' => true],
                    ['symbol' => 'TEST3', 'isTradable' => true, 'isExchangeCrypto' => true],
                ],
                'expectedCryptosList' => 'TEST1, TEST2 and TEST3',
            ],
            'Only TEST1 and TEST2 sent in mintme exchange mail' => [
                'cryptoData' => [
                    ['symbol' => 'TEST1', 'isTradable' => true, 'isExchangeCrypto' => true],
                    ['symbol' => 'TEST2', 'isTradable' => true, 'isExchangeCrypto' => true],
                    ['symbol' => 'TEST3', 'isTradable' => false, 'isExchangeCrypto' => false],
                ],
                'expectedCryptosList' => 'TEST1 and TEST2',
            ],
            'Only TEST3 sent in mintme exchange mail' => [
                'cryptoData' => [
                    ['symbol' => 'TEST1', 'isTradable' => false, 'isExchangeCrypto' => false],
                    ['symbol' => 'TEST2', 'isTradable' => false, 'isExchangeCrypto' => false],
                    ['symbol' => 'TEST3', 'isTradable' => true, 'isExchangeCrypto' => true],
                ],
                'expectedCryptosList' => 'TEST3',
            ],
            'Only TEST1 and TEST3 sent in mintme exchange mail' => [
                'cryptoData' => [
                    ['symbol' => 'TEST1', 'isTradable' => true, 'isExchangeCrypto' => true],
                    ['symbol' => 'TEST2', 'isTradable' => false, 'isExchangeCrypto' => false],
                    ['symbol' => 'TEST3', 'isTradable' => true, 'isExchangeCrypto' => true],
                ],
                'expectedCryptosList' => 'TEST1 and TEST3',
            ],
        
        ];
    }

    /** @return UserTokenRepository|MockObject */
    private function mockUserTokenRepository(): UserTokenRepository
    {
        return $this->createMock(UserTokenRepository::class);
    }

    /** @return Token|MockObject */
    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    /** @return Crypto|MockObject */
    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    /** @return PasswordUpdaterInterface|MockObject */
    private function mockPasswordUpdater(): PasswordUpdaterInterface
    {
        return $this->createMock(PasswordUpdaterInterface::class);
    }

    /** @return CanonicalFieldsUpdater|MockObject */
    private function mockCanonicalFieldsUpdater(): CanonicalFieldsUpdater
    {
        return $this->createMock(CanonicalFieldsUpdater::class);
    }

    /** @return CryptoManagerInterface|MockObject */
    private function mockCryptoManagerInterface(): CryptoManagerInterface
    {
        return $this->createMock(CryptoManagerInterface::class);
    }

    /** @return MailerInterface|MockObject */
    private function mockMailerInterface(): MailerInterface
    {
        return $this->createMock(MailerInterface::class);
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEntityManagerInterface(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /** @return ObjectManager|MockObject */
    private function mockObjectManager(UserRepository $repository): ObjectManager
    {
        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }

    /** @return UserRepository|MockObject */
    private function mockRepository(?User $user): UserRepository
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('find')->willReturn($user);
        $repo->method('findOneBy')->willReturn($user);

        return $repo;
    }

    /** @return User|MockObject */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
