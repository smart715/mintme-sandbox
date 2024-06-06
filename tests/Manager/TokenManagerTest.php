<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Crypto;
use App\Entity\DeployTokenReward;
use App\Entity\Profile;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\Voting\TokenVoting;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\Config;
use App\Exchange\Config\TokenConfig;
use App\Manager\CryptoManager;
use App\Manager\TokenManager;
use App\Repository\DeployNotificationRepository;
use App\Repository\DeployTokenRewardRepository;
use App\Repository\TokenRepository;
use App\Repository\TokenVotingRepository;
use App\Utils\Converter\String\DashStringStrategy;
use App\Utils\Converter\TokenNameConverter;
use App\Utils\Fetcher\ProfileFetcherInterface;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenManagerTest extends TestCase
{
    public function testGetRepository(): void
    {
        $tokenRepository = $this->mockTokenRepository();

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($tokenRepository, $tokenManager->getRepository());
    }

    /** @dataProvider findByHiddenNameDataProvider */
    public function testFindByHiddenName(int $expected, string $origin): void
    {
        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->once())
            ->method('find')
            ->with($expected)
            ->willReturn($this->mockToken('TOKEN'));

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(1),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
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

    public function testFindByName(): void
    {
        $name = 'TOKEN';
        $token = $this->mockToken($name);

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->method('findByName')
            ->with($name)
            ->willReturnOnConsecutiveCalls($token, null);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($token, $tokenManager->findByName($name));
        $this->assertNull($tokenManager->findByName($name));
    }

    public function testFindByUrl(): void
    {
        $url = 'TEST';
        $token = $this->mockToken('TOKEN');

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->exactly(2))
            ->method('findByUrl')
            ->with($url)
            ->willReturnOnConsecutiveCalls($token, null);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($token, $tokenManager->findByUrl($url));
        $this->assertNull($tokenManager->findByUrl($url));
    }

    public function testFindById(): void
    {
        $tokenId = 1;
        $token = $this->mockToken('TOKEN');

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->with($tokenId)
            ->willReturnOnConsecutiveCalls($token, null);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($token, $tokenManager->findById($tokenId));
        $this->assertNull($tokenManager->findById($tokenId));
    }

    public function testGetRandomTokens(): void
    {
        $limit = 2;
        $tokens = [
            $this->mockToken('TOKEN1'),
            $this->mockToken('TOKEN2'),
        ];

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->once())
            ->method('getRandomTokens')
            ->with($limit)
            ->willReturn($tokens);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($tokens, $tokenManager->getRandomTokens($limit));
    }

    public function testFindByNameCrypto(): void
    {
        $tokenName = 'TOKEN';
        $token = $this->mockToken($tokenName);
        $token
            ->method('getCryptoSymbol')
            ->willReturn(Symbols::ETH);

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->exactly(2))
            ->method('findByName')
            ->with($tokenName)
            ->willReturn($token);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($token, $tokenManager->findByNameCrypto($tokenName, Symbols::ETH));
        $this->assertNull($tokenManager->findByNameCrypto($tokenName, Symbols::BNB));
    }

    public function testFindByNameMintme(): void
    {
        $tokenName = 'TOKEN';
        $token = $this->mockToken($tokenName);
        $token
            ->method('getCryptoSymbol')
            ->willReturn(Symbols::WEB);

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->exactly(2))
            ->method('findByName')
            ->with($tokenName)
            ->willReturnOnConsecutiveCalls($token, null);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($token, $tokenManager->findByNameMintme($tokenName));
        $this->assertNull($tokenManager->findByNameMintme($tokenName));
    }

    public function testGetOwnMintmeToken(): void
    {
        $token = $this->mockToken('TOKEN');
        $token
            ->method('isMintmeToken')
            ->willReturn(true);

        $profile = $this->mockProfile();
        $profile
            ->method('getMintmeToken')
            ->willReturnOnConsecutiveCalls($token, null);

        $profileFetcher = $this->mockProfileFetcher();
        $profileFetcher
            ->method('fetchProfile')
            ->willReturn($profile);

        $tokenManager = new TokenManager(
            $profileFetcher,
            $this->mockConfig(0),
            $this->mockTokenRepository(),
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($token, $tokenManager->getOwnMintmeToken());
        $this->assertNull($tokenManager->getOwnMintmeToken());
    }

    public function testGetOwnTokenByName(): void
    {
        $user = $this->mockUser();

        $tokens = [
            $this->mockToken('TOKEN1'),
            $this->mockToken('TOKEN2'),
        ];

        $profile = $this->mockProfile();
        $profile
            ->method('getUser')
            ->willReturn($user);
        $profile
            ->method('getTokens')
            ->willReturn($tokens);

        $profileFetcher = $this->mockProfileFetcher();
        $profileFetcher
            ->method('fetchProfile')
            ->willReturn($profile);

        $tokenManager = new TokenManager(
            $profileFetcher,
            $this->mockConfig(0),
            $this->mockTokenRepository(),
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($tokens[0], $tokenManager->getOwnTokenByName('TOKEN1'));
        $this->assertNull($tokenManager->getOwnTokenByName('TOKEN3'));
    }

    public function testGetOwnTokens(): void
    {
        $user = $this->mockUser();

        $tokens = [
            $this->mockToken('TOKEN1'),
            $this->mockToken('TOKEN2'),
        ];

        $profile = $this->mockProfile();
        $profile
            ->method('getUser')
            ->willReturn($user);
        $profile
            ->method('getTokens')
            ->willReturn($tokens);

        $profileFetcher = $this->mockProfileFetcher();
        $profileFetcher
            ->method('fetchProfile')
            ->willReturn($profile);

        $tokenManager = new TokenManager(
            $profileFetcher,
            $this->mockConfig(0),
            $this->mockTokenRepository(),
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($tokens, $tokenManager->getOwnTokens());
    }

    public function testGetOwnDeployedTokens(): void
    {
        $user = $this->mockUser();

        $tokens = [
            $this->mockToken('TOKEN1', null, true, $user, true),
            $this->mockToken('TOKEN2', null, true, $user, true),
        ];

        $profile = $this->mockProfile();
        $profile
            ->method('getUser')
            ->willReturn($user);
        $profile
            ->method('getTokens')
            ->willReturn($tokens);

        $profileFetcher = $this->mockProfileFetcher();
        $profileFetcher
            ->method('fetchProfile')
            ->willReturn($profile);

        $tokenManager = new TokenManager(
            $profileFetcher,
            $this->mockConfig(0),
            $this->mockTokenRepository(),
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals($tokens, $tokenManager->getOwnDeployedTokens());
    }

    public function testGetTokensCount(): void
    {
        $user = $this->mockUser();

        $tokens = [
            $this->mockToken('TOKEN1'),
            $this->mockToken('TOKEN2'),
        ];

        $profile = $this->mockProfile();
        $profile
            ->method('getUser')
            ->willReturn($user);
        $profile
            ->method('getTokens')
            ->willReturn($tokens);

        $profileFetcher = $this->mockProfileFetcher();
        $profileFetcher
            ->method('fetchProfile')
            ->willReturn($profile);

        $tokenManager = new TokenManager(
            $profileFetcher,
            $this->mockConfig(0),
            $this->mockTokenRepository(),
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals(2, $tokenManager->getTokensCount());
    }

    /** @dataProvider getRealBalanceProvider */
    public function testGetRealBalance(
        Token $token,
        bool $hasProfile,
        int $eAvailable,
        int $eFreeze,
        int $eReferral
    ): void {
        $user = $token->getProfile()->getUser();

        $profile = $this->mockProfile();
        $profile->method('getTokens')->willReturn([$token]);
        $profile->method('getUser')->willReturn($user);

        $profileFetcher = $this->mockProfileFetcher();
        $profileFetcher->method('fetchProfile')->willReturn($hasProfile ? $profile: null);

        $tokenManager = new TokenManager(
            $profileFetcher,
            $this->mockConfig(0),
            $this->mockTokenRepository(),
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $amount = $this->mockMoney(1);

        $br = $this->createMock(BalanceResult::class);
        $br->method('getReferral')->willReturn($amount);
        $br->method('getFreeze')->willReturn($amount);
        $br->method('getAvailable')->willReturn($amount);

        $res = $tokenManager->getRealBalance($token, $br, $user);

        $this->assertEquals($eAvailable, $res->getAvailable()->getAmount());
        $this->assertEquals($eFreeze, $res->getFreeze()->getAmount());
        $this->assertEquals($eReferral, $res->getReferral()->getAmount());
    }

    public function getRealBalanceProvider(): array
    {
        return [
            [$this->mockToken('foo'), true, 1, 1, 1],
            [$this->mockToken('foo', $this->mockLockIn(1)), true, 0, 2, 1],
            [$this->mockToken('foo', $this->mockLockIn(1), false, $this->mockUser()), true, 1, 1, 1],
            [$this->mockToken('foo', $this->mockLockIn(1), true, $this->mockUser()), true, 0, 2, 1],
            [$this->mockToken('foo', $this->mockLockIn(1), true, $this->mockUser(), true), true, 0, 2, 1],
        ];
    }

    public function testIsExisted(): void
    {
        $fooTok = $this->mockToken(' foo   23-fg  fd  ');

        $ninjoTok = $this->mockToken('ninjo');

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository->method('findByName')->willReturn($fooTok);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertTrue($tokenManager->isExisted($fooTok->getName()));
        $this->assertFalse($tokenManager->isExisted($ninjoTok->getName()));
    }

    public function testGetDeployedTokens(): void
    {
        $offset = 0;
        $limit = 10;
        $token = $this->mockToken('TOKEN');

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->once())
            ->method('getDeployedTokens')
            ->with($offset, $limit)
            ->willReturn([$token]);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals([$token], $tokenManager->getDeployedTokens($offset, $limit));
    }

    public function testGetUserAllDeployTokensReward(): void
    {
        $cryptos = $this->mockCryptos();

        foreach ($cryptos as $crypto) {
            $symbol = $crypto->getSymbol();
            $user = $this->mockUser();
            $deployTokenRewardRepository = $this->mockDeployTokenRewardRepository();
            $deployTokenRewardRepository->expects($this->once())
                ->method('findBy')
                ->with(['user' => $user, 'currency' => $symbol])
                ->willReturn([
                    new DeployTokenReward($user, new Money(10, new Currency($symbol))),
                    new DeployTokenReward($user, new Money(10, new Currency($symbol))),
                ]);

            $tokenManager = new TokenManager(
                $this->mockProfileFetcher(),
                $this->mockConfig(0),
                $this->mockTokenRepository(),
                $this->mockTokenVotingRepository(),
                $deployTokenRewardRepository,
                new DashStringStrategy(),
                $this->createMock(CryptoManager::class),
                $this->createMock(TokenConfig::class),
                $this->mockTokenNameConverter(),
                $this->createMock(DeployNotificationRepository::class)
            );

            $referralReward = $tokenManager->getUserAllDeployTokensReward($user, [$crypto]);

            foreach ($referralReward as $referral) {
                $this->assertEquals('20', $referral->getAmount());
                $this->assertEquals($symbol, $referral->getCurrency());
            }
        }
    }

    public function testFindAllTokensWithEmptyDescription(): void
    {
        $param = 14;
        $token = $this->mockToken('TOKEN');

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->once())
            ->method('findAllTokensWithEmptyDescription')
            ->with($param)
            ->willReturn([$token]);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals([$token], $tokenManager->findAllTokensWithEmptyDescription($param));
    }

    public function testGetTokensWithoutAirdrops(): void
    {
        $token = $this->mockToken('TOKEN');

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->once())
            ->method('getTokensWithoutAirdrops')
            ->willReturn([$token]);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals([$token], $tokenManager->getTokensWithoutAirdrops());
    }

    public function testGetTokensWithAirdrops(): void
    {
        $token = $this->mockToken('TOKEN');

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->once())
            ->method('getTokensWithAirdrops')
            ->willReturn([$token]);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals([$token], $tokenManager->getTokensWithAirdrops());
    }

    public function testGetNotOwnTokens(): void
    {
        $user = $this->mockUser();
        $token = $this->mockToken('TOKEN');

        $tokenRepository = $this->mockTokenRepository();
        $tokenRepository
            ->expects($this->once())
            ->method('getNotOwnTokens')
            ->with($user)
            ->willReturn([$token]);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $tokenRepository,
            $this->mockTokenVotingRepository(),
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals([$token], $tokenManager->getNotOwnTokens($user));
    }

    public function testGetVotingByTokenId(): void
    {
        $tokenId = 1;
        $offset = 0;
        $limit = 10;
        $tokenVoting = $this->mockTokenVoting();

        $tokenVotingRepository = $this->mockTokenVotingRepository();
        $tokenVotingRepository
            ->expects($this->once())
            ->method('getVotingByTokenId')
            ->with($tokenId, $offset, $limit)
            ->willReturn([$tokenVoting]);

        $tokenManager = new TokenManager(
            $this->mockProfileFetcher(),
            $this->mockConfig(0),
            $this->mockTokenRepository(),
            $tokenVotingRepository,
            $this->mockDeployTokenRewardRepository(),
            new DashStringStrategy(),
            $this->createMock(CryptoManager::class),
            $this->createMock(TokenConfig::class),
            $this->mockTokenNameConverter(),
            $this->createMock(DeployNotificationRepository::class)
        );

        $this->assertEquals([$tokenVoting], $tokenManager->getVotingByTokenId($tokenId, $offset, $limit));
    }

    private function mockCryptos(): array
    {
        $WEB = $this->createMock(Crypto::class);
        $WEB->method('getSymbol')
            ->willReturn(Symbols::WEB);

        $ETH = $this->createMock(Crypto::class);
        $ETH->method('getSymbol')
            ->willReturn(Symbols::ETH);

        $BNB = $this->createMock(Crypto::class);
        $BNB->method('getSymbol')
            ->willReturn(Symbols::BNB);

        return [$WEB, $ETH, $BNB];
    }

    /** @return MockObject|Token */
    private function mockToken(
        string $name,
        ?LockIn $lockIn = null,
        bool $isOwner = true,
        ?User $user = null,
        bool $deployed = false
    ): Token {
        $profile = $this->mockProfile();
        $profile->method('getUser')->willReturn($user ?? $this->mockUser());

        $tok = $this->createMock(Token::class);
        $tok->method('getName')->willReturn($name);
        $tok->method('getLockIn')->willReturn($lockIn);
        $tok->method('getProfile')->willReturn($profile);
        $tok->method('isDeployed')->willReturn($deployed);
        $tok->method('isOwner')->willReturn($isOwner);

        return $tok;
    }

    /** @return MockObject|LockIn */
    private function mockLockIn(int $frozen): LockIn
    {
        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getFrozenAmountWithReceived')->willReturn($this->mockMoney($frozen));
        $lockIn->method('getFrozenAmount')->willReturn($this->mockMoney($frozen));

        return $lockIn;
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

    /** @return MockObject|TokenVotingRepository */
    private function mockTokenVotingRepository(): TokenVotingRepository
    {
        return $this->createMock(TokenVotingRepository::class);
    }

    /** @return MockObject|TokenRepository */
    private function mockTokenRepository(): TokenRepository
    {
        return $this->createMock(TokenRepository::class);
    }

    /** @return MockObject|ProfileFetcherInterface */
    private function mockProfileFetcher(): ProfileFetcherInterface
    {
        return $this->createMock(ProfileFetcherInterface::class);
    }

    /** @return MockObject|DeployTokenRewardRepository */
    private function mockDeployTokenRewardRepository(): DeployTokenRewardRepository
    {
        return $this->createMock(DeployTokenRewardRepository::class);
    }

    /** @return MockObject|TokenVoting */
    private function mockTokenVoting(): TokenVoting
    {
        return $this->createMock(TokenVoting::class);
    }

    /** @return MockObject|User */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    /** @return MockObject|Profile */
    private function mockProfile(): Profile
    {
        return $this->createMock(Profile::class);
    }

    public function mockTokenNameConverter(): TokenNameConverter
    {
        return $this->createMock(TokenNameConverter::class);
    }
}
