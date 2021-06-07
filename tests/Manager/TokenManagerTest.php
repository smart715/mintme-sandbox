<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Crypto;
use App\Entity\DeployTokenReward;
use App\Entity\Profile;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\Config;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManager;
use App\Repository\DeployTokenRewardRepository;
use App\Repository\TokenRepository;
use App\Utils\Fetcher\ProfileFetcherInterface;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenManagerTest extends TestCase
{
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
        $this->assertEquals($token, $tokenManager->getOwnMintmeToken());
    }

    /** @dataProvider findByHiddenNameDataProvider */
    public function testFindByHiddenName(int $expected, string $origin): void
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

        $ninjoTok = $this->mockToken('ninjo');

        /** @var MockObject|TokenRepository $tokenRepository */
        $tokenRepository = $this->createMock(TokenRepository::class);

        $tokenRepository->method('findByName')->willReturn($fooTok);

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
        $this->assertFalse($tokenManager->isExisted($ninjoTok->getName()));
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

        $amount = $this->mockMoney(1);

        $br = $this->createMock(BalanceResult::class);
        $br->method('getReferral')->willReturn($amount);
        $br->method('getFreeze')->willReturn($amount);
        $br->method('getAvailable')->willReturn($amount);

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
            [$this->mockToken('foo', $this->mockLockIn(1), $this->createMock(User::class), true), true, 0, 2, 1],
        ];
    }

    public function testGetUserDeployTokensReward(): void
    {
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        $repo = $this->createMock(DeployTokenRewardRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn([
                new DeployTokenReward($user, new Money(5, new Currency(Symbols::WEB))),
                new DeployTokenReward($user, new Money(5, new Currency(Symbols::WEB))),
            ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $tokenManager = new TokenManager(
            $em,
            $this->createMock(ProfileFetcherInterface::class),
            $this->createMock(TokenStorageInterface::class),
            $this->mockCryptoManager([
                $this->mockCrypto('foo'),
                $this->mockCrypto('bar'),
            ]),
            $this->mockConfig(0)
        );

        $referralReward = $tokenManager->getUserDeployTokensReward($user);

        $this->assertEquals('10', $referralReward->getAmount());
        $this->assertEquals(Symbols::WEB, $referralReward->getCurrency());
    }

    private function mockToken(string $name, ?LockIn $lockIn = null, ?User $user = null, bool $deployed = false): Token
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($user ?? $this->createMock(User::class));

        $tok = $this->createMock(Token::class);
        $tok->method('getName')->willReturn($name);
        $tok->method('getLockIn')->willReturn($lockIn);
        $tok->method('getProfile')->willReturn($profile);
        $tok->method('isDeployed')->willReturn($deployed);

        return $tok;
    }

    private function mockLockIn(int $frozen): LockIn
    {
        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getFrozenAmountWithReceived')->willReturn($this->mockMoney($frozen));
        $lockIn->method('getFrozenAmount')->willReturn($this->mockMoney($frozen));

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

    private function mockMoney(int $amount, ?string $symbol = null): Money
    {
        return new Money($amount, new Currency($symbol ?? Symbols::TOK));
    }
}
