<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\Config;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManager;
use App\Repository\TokenRepository;
use App\Utils\Fetcher\ProfileFetcherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenManagerTest extends TestCase
{
    public function testFindByNameWithCrypto(): void
    {
        $name = 'TOKEN';

        /** @var MockObject|EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $tokenManager = new TokenManager(
            $entityManager,
            $this->createMock(ProfileFetcherInterface::class),
            $this->mockTokenStorage(),
            $this->mockCryptoManager([$this->mockCrypto($name)]),
            $this->mockConfig(0)
        );

        $this->assertEquals($name, $tokenManager->findByName($name)->getCrypto()->getName());
        $this->assertEquals($name, $tokenManager->findByName($name)->getName());
        $this->assertNotSame($tokenManager->findByName($name), $tokenManager->findByName($name));
    }

    public function testFindByName(): void
    {
        $name = 'TOKEN';
        $token = $this->createMock(Token::class);

        /** @var MockObject|TokenRepository $tokenRepository */
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository->method('findByName')->with($name)->willReturn($token);

        /** @var MockObject|EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($tokenRepository);

        $tokenManager = new TokenManager(
            $entityManager,
            $this->createMock(ProfileFetcherInterface::class),
            $this->mockTokenStorage(),
            $this->mockCryptoManager([]),
            $this->mockConfig(0)
        );

        $this->assertEquals($token, $tokenManager->findByName($name));
    }

    public function testGetOwnTokenWhenProfileCreated(): void
    {
        $token = $this->createMock(Token::class);

        $profile = $this->createMock(Profile::class);
        $profile->method('getToken')->willReturn($token);

        /** @var MockObject|ProfileFetcherInterface $profileFetcher */
        $profileFetcher = $this->createMock(ProfileFetcherInterface::class);
        $profileFetcher->method('fetchProfile')->willReturn($profile);

        $tokenManager = new TokenManager(
            $this->createMock(EntityManagerInterface::class),
            $profileFetcher,
            $this->mockTokenStorage(),
            $this->mockCryptoManager([]),
            $this->mockConfig(0)
        );
        $this->assertEquals($token, $tokenManager->getOwnToken());
    }

    public function testGetOwnTokenWhenProfileNotCreated(): void
    {
        /** @var MockObject|ProfileFetcherInterface $profileFetcher */
        $profileFetcher = $this->createMock(ProfileFetcherInterface::class);
        $profileFetcher->method('fetchProfile')->willReturn(null);

        $tokenManager = new TokenManager(
            $this->createMock(EntityManagerInterface::class),
            $profileFetcher,
            $this->mockTokenStorage(),
            $this->mockCryptoManager([]),
            $this->mockConfig(0)
        );
        $this->assertEquals(null, $tokenManager->getOwnToken());
    }

    /** @dataProvider findByHiddenNameDataProvider */
    public function testFindByHiddenName(string $expected, string $origin): void
    {
        $repo = $this->createMock(TokenRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($expected)
            ->willReturn($this->createMock(Token::class));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $tokenManager = new TokenManager(
            $em,
            $this->createMock(ProfileFetcherInterface::class),
            $this->mockTokenStorage(),
            $this->mockCryptoManager([]),
            $this->mockConfig(1)
        );

        $tokenManager->findByHiddenName($origin);
    }

    public function findByHiddenNameDataProvider(): array
    {
        return [
            [122, '123'],
            [122, 'qwe123'],
            [122, 'qwe123qwe'],
            [-1, 'qwe'],
        ];
    }

    public function testIsExisted(): void
    {
        $fooTok = $this->mockToken(' foo   23-fg  fd  ');

        $barTok = $this->mockToken('bar');
        $ninjoTok = $this->mockToken('ninjo');

        /** @var MockObject|TokenRepository $tokenRepository */
        $tokenRepository = $this->createMock(TokenRepository::class);

        $tokenRepository->expects($this->at(0))->method('findByName')
            ->with('FOO-23-FG-FD')->willReturn($barTok);

        $tokenRepository->expects($this->at(1))->method('findByName')
            ->with('FOO 23 FG FD')->willReturn($ninjoTok);


        /** @var MockObject|EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($tokenRepository);

        $tokenManager = new TokenManager(
            $entityManager,
            $this->createMock(ProfileFetcherInterface::class),
            $this->mockTokenStorage(),
            $this->mockCryptoManager([]),
            $this->mockConfig(0)
        );

        $this->assertTrue($tokenManager->isExisted($fooTok->getName()));
        $this->assertFalse($tokenManager->isExisted($barTok->getName()));
    }

    /** @dataProvider getRealBalanceProvider */
    public function testGetRealBalance(
        Token $token,
        bool $hasProfile,
        int $eAvailable,
        int $eFreeze,
        int $eReferral
    ): void {
        $profile = $this->createMock(Profile::class);
        $profile->method('getToken')->willReturn($token);
        $profile->method('getUser')->willReturn($token->getProfile()->getUser());

        /** @var MockObject|ProfileFetcherInterface $profileFetcher */
        $profileFetcher = $this->createMock(ProfileFetcherInterface::class);
        $profileFetcher->method('fetchProfile')->willReturn($hasProfile ? $profile: null);

        $tokenManager = new TokenManager(
            $this->createMock(EntityManagerInterface::class),
            $profileFetcher,
            $this->mockTokenStorage($token->getProfile()->getUser()),
            $this->mockCryptoManager([]),
            $this->mockConfig(0)
        );

        $br = $this->createMock(BalanceResult::class);
        $br->method('getReferral')->willReturn(Money::USD(1));
        $br->method('getFreeze')->willReturn(Money::USD(1));
        $br->method('getAvailable')->willReturn(Money::USD(1));

        $res = $tokenManager->getRealBalance($token, $br);

        $this->assertEquals($eAvailable, $res->getAvailable()->getAmount());
        $this->assertEquals($eFreeze, $res->getFreeze()->getAmount());
        $this->assertEquals($eReferral, $res->getReferral()->getAmount());
    }

    public function getRealBalanceProvider(): array
    {
        return [
            [$this->mockToken('foo'), true, 1, 1, 1],
            [$this->mockToken('foo', $this->mockLockIn(1)), true, 0, 2, 1],
            [$this->mockToken('foo', $this->mockLockIn(1), $this->createMock(User::class)), true, 0, 2, 1],
        ];
    }

    public function testFindAllPredefined(): void
    {
        $tokenManager = new TokenManager(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ProfileFetcherInterface::class),
            $this->createMock(TokenStorageInterface::class),
            $this->mockCryptoManager([
                $this->mockCrypto('foo'),
                $this->mockCrypto('bar'),
            ]),
            $this->mockConfig(0)
        );

        $toks = $tokenManager->findAllPredefined();

        $this->assertCount(2, $toks);
        $this->assertEquals(['foo', 'bar'], [
            $toks[0]->getSymbol(),
            $toks[1]->getSymbol(),
        ]);
    }

    private function mockToken(string $name, ?LockIn $lockIn = null, ?User $user = null): Token
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($user ?? $this->createMock(User::class));

        $tok = $this->createMock(Token::class);
        $tok->method('getName')->willReturn($name);
        $tok->method('getLockIn')->willReturn($lockIn);
        $tok->method('getProfile')->willReturn($profile);

        return $tok;
    }

    private function mockLockIn(int $frozen): LockIn
    {
        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getFrozenAmount')->willReturn(Money::USD($frozen));

        return $lockIn;
    }

    /** @return MockObject|TokenStorageInterface */
    private function mockTokenStorage(?User $user = null): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        return $storage;
    }

    /**
     * @param Crypto[] $cryptos
     * @return MockObject|CryptoManagerInterface
     */
    private function mockCryptoManager(array $cryptos): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);

        $manager->method('findAll')->willReturn($cryptos);
        $manager->method('findBySymbol')->willReturnCallback(function (string $str) use ($cryptos) {
            return $cryptos[
                array_search($str, array_map(function (Crypto $crypto) {
                    return $crypto->getSymbol();
                }, $cryptos)) ?: 0
            ];
        });

        return $manager;
    }

    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);

        $crypto->method('getSymbol')->willReturn($symbol);
        $crypto->method('getName')->willReturn($symbol);

        return $crypto;
    }

    /** @return MockObject|Config */
    private function mockConfig(int $offset): Config
    {
        $config = $this->createMock(Config::class);

        $config->method('getOffset')->willReturn($offset);

        return $config;
    }
}
