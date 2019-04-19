<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Exchange\Config\Config;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManager;
use App\Repository\TokenRepository;
use App\Utils\Converter\TokenNameNormalizerInterface;
use App\Utils\Fetcher\ProfileFetcherInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
            $this->createMock(TokenNameNormalizerInterface::class),
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
            $this->createMock(TokenNameNormalizerInterface::class),
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
            $this->createMock(TokenNameNormalizerInterface::class),
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
            $this->createMock(TokenNameNormalizerInterface::class),
            $this->mockConfig(0)
        );
        $this->assertEquals(null, $tokenManager->getOwnToken());
    }

    /** @return MockObject|TokenStorageInterface */
    private function mockTokenStorage(): TokenStorageInterface
    {
        return $this->createMock(TokenStorageInterface::class);
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
