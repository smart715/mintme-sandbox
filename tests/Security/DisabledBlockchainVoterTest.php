<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\Crypto;
use App\Security\Config\DisabledBlockchainConfig;
use App\Security\DisabledBlockchainVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DisabledBlockchainVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(string $cryptoSymbol, array $disabledCryptos, bool $result): void
    {
        $voter = new DisabledBlockchainVoter($this->mockDisabledBlockchain($disabledCryptos));
        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                ['not-disabled', $this->mockCrypto($cryptoSymbol), $this->mockTokenInterface()]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(string $attribute, bool $result): void
    {
        $voter = new DisabledBlockchainVoter($this->mockDisabledBlockchain());
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($voter, [$attribute, $this->mockCrypto()])
        );
    }

    public function voteAttributesProvider(): array
    {
        return [
            "True if crypto is not disabled" => ['BTC', ['ETH', 'LTC'], true],
            "False if crypto is disabled" => ['ETH', ['ETH', 'LTC'], false],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "True if attribute is supported" => ['not-disabled', true],
            "False if attribute is not supported" => ['not-supported', false],
        ];
    }

    private function mockTokenInterface(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    private function mockDisabledBlockchain(array $disabledCryptos = []): DisabledBlockchainConfig
    {
        $disabledBlockchain = $this->createMock(DisabledBlockchainConfig::class);
        $disabledBlockchain->method('getDisabledCryptoSymbols')->willReturn($disabledCryptos);

        return $disabledBlockchain;
    }

    private function mockCrypto(string $cryptoSymbol = ""): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($cryptoSymbol);

        return $crypto;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(DisabledBlockchainVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
