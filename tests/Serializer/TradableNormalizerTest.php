<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TradableInterface;
use App\Serializer\TradableNormalizer;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TradableNormalizerTest extends TestCase
{
    private const TOKEN_NAME = 'TOK';
    private const CRYPTO_SYMBOL = 'WEB';
    private const CONVERTED_TOK_NAME = 'TOK-converted';
    private const REBRANDED = 'rebranded';

    private const TOK_SUBUNIT = 4;
    private const CRYPTO_SUBUNIT = 8;

    public function testSupportsNormalizationSuccess(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->createMock(TradableInterface::class);

        $this->assertTrue($normalizer->supportsNormalization($tradable));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $object = new \stdClass();

        $this->assertFalse($normalizer->supportsNormalization($object));
    }

    public function testNormalizeWithCrypto(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockCrypto();
        $context = [];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [],
            $normalizedTradable
        );
    }

    public function testNormalizeWithCryptoWithDefaultGroup(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockCrypto();
        $context = [
            'groups' => ['Default'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [
                'identifier' => self::CRYPTO_SYMBOL,
                'subunit' => self::CRYPTO_SUBUNIT,
            ],
            $normalizedTradable
        );
    }

    public function testNormalizeWithCryptoWithApiGroup(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockCrypto();
        $context = [
            'groups' => ['API'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [
                'identifier' => self::CRYPTO_SYMBOL,
                'subunit' => self::CRYPTO_SUBUNIT,
            ],
            $normalizedTradable
        );
    }

    public function testNormalizeWithCryptoWithDevGroup(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockCrypto();
        $context = [
            'groups' => ['dev'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [
                'name' => self::CRYPTO_SYMBOL . self::REBRANDED,
                'symbol' => self::CRYPTO_SYMBOL . self::REBRANDED,
            ],
            $normalizedTradable
        );
    }

    public function testNormalizeWithCryptoWithMarketStatusGroup(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockCrypto();
        $context = [
            'groups' => ['MARKET_STATUS'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [],
            $normalizedTradable
        );
    }

    public function testNormalizeWithToken(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockToken();
        $context = [];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [],
            $normalizedTradable
        );
    }

    public function testNormalizeWithTokenWithDefaultGroup(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockToken();
        $context = [
            'groups' => ['Default'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [
                'identifier' => self::CONVERTED_TOK_NAME,
                'subunit' => self::TOK_SUBUNIT,
            ],
            $normalizedTradable
        );
    }

    public function testNormalizeWithTokenWithApiGroup(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockToken();
        $context = [
            'groups' => ['API'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [
                'identifier' => self::CONVERTED_TOK_NAME,
                'subunit' => self::TOK_SUBUNIT,
            ],
            $normalizedTradable
        );
    }

    public function testNormalizeWithTokenWithDevGroup(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockToken();
        $context = [
            'groups' => ['dev'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [
                'name' => self::TOKEN_NAME . self::REBRANDED,
                'symbol' => self::TOKEN_NAME . self::REBRANDED,
            ],
            $normalizedTradable
        );
    }

    public function testNormalizeWithTokenWithMarketStatusGroup(): void
    {
        $normalizer = $this->getTradableNormalizer(true);
        $tradable = $this->mockToken();
        $context = [
            'groups' => ['MARKET_STATUS'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [],
            $normalizedTradable
        );
    }

    public function testNormalizeWithTokenWithMarketStatusGroupWithIgnoreAttributes(): void
    {
        $normalizer = $this->getTradableNormalizer(true);
        $tradable = $this->mockToken();
        $context = [
            'groups' => ['MARKET_STATUS'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => [],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [],
            $normalizedTradable
        );
    }

    public function testNormalizeWithDeployedTokenWithMarketStatusGroup(): void
    {
        $normalizer = $this->getTradableNormalizer(true);
        $tradable = $this->mockToken(true);
        $context = [
            'groups' => ['MARKET_STATUS'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [],
            $normalizedTradable
        );
    }

    public function testNormalizeWithDeployedToken(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockToken(true);
        $context = [];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [
                'networks' => [
                    self::CRYPTO_SYMBOL,
                ],
            ],
            $normalizedTradable
        );
    }

    public function testNormalizeWithDeployedTokenWithDevGroup(): void
    {
        $normalizer = $this->getTradableNormalizer();
        $tradable = $this->mockToken(true);
        $context = [
            'groups' => ['dev'],
        ];

        $normalizedTradable = $normalizer->normalize($tradable, null, $context);

        $this->assertEquals(
            [
                'networks' => [
                    self::CRYPTO_SYMBOL . self::REBRANDED,
                ],
                'name' => self::TOKEN_NAME . self::REBRANDED,
                'symbol' => self::TOKEN_NAME . self::REBRANDED,
            ],
            $normalizedTradable
        );
    }

    private function getTradableNormalizer(bool $ignoreHolders = false): TradableNormalizer
    {
        return new TradableNormalizer(
            $this->mockObjectNormalizer($ignoreHolders),
            $this->mockTokenNameConverter(),
            $this->mockRebrandingConverter(),
            self::TOK_SUBUNIT
        );
    }

    private function mockObjectNormalizer(bool $ignoreHolders = false): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer
            ->method('normalize')
            ->willReturnCallback(function ($object, $format, array $context) use ($ignoreHolders): array {
                if ($ignoreHolders) {
                    $ignoreHoldersCount = in_array('holdersCount', $context[AbstractNormalizer::IGNORED_ATTRIBUTES])
                        || in_array(['holdersCount'], $context[AbstractNormalizer::IGNORED_ATTRIBUTES]);
                    $this->assertTrue($ignoreHoldersCount);
                }

                return [];
            });

        return $objectNormalizer;
    }

    private function mockTokenNameConverter(): TokenNameConverterInterface
    {
        $tokenNameConverter = $this->createMock(TokenNameConverterInterface::class);
        $tokenNameConverter
            ->method('convert')
            ->willReturn(self::CONVERTED_TOK_NAME);

        return $tokenNameConverter;
    }

    private function mockRebrandingConverter(): RebrandingConverterInterface
    {
        $rebrandingConverter = $this->createMock(RebrandingConverterInterface::class);
        $rebrandingConverter
            ->method('convert')
            ->willReturnCallback(function (string $symbol): string {
                return $symbol . self::REBRANDED;
            });

        return $rebrandingConverter;
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getName')->willReturn(self::CRYPTO_SYMBOL);
        $crypto->method('getSymbol')->willReturn(self::CRYPTO_SYMBOL);
        $crypto->method('getShowSubunit')->willReturn(self::CRYPTO_SUBUNIT);

        return $crypto;
    }

    private function mockToken(bool $isDeployed = false): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getName')->willReturn(self::TOKEN_NAME);
        $token->method('getSymbol')->willReturn(self::TOKEN_NAME);
        $token->method('isDeployed')->willReturn($isDeployed);
        $token->method('getDeploys')->willReturn($isDeployed ? [$this->mockTokenDeploy()] : []);

        return $token;
    }

    private function mockTokenDeploy(): TokenDeploy
    {
        $tokenDeploy = $this->createMock(TokenDeploy::class);
        $tokenDeploy->method('getCrypto')->willReturn($this->mockCrypto());

        return $tokenDeploy;
    }
}
