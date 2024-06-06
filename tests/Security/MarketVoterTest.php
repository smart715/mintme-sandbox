<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;
use App\Entity\TradableInterface;
use App\Exchange\Market;
use App\Manager\TokenCryptoManagerInterface;
use App\Security\MarketVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MarketVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(string $attribute, bool $exist, bool $result, bool $returnToken = true): void
    {
        $voter = new MarketVoter($this->mockTokenCryptoManager($exist));
        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, $this->mockMarket($returnToken), $this->mockTokenInterface()]
            )
        );
    }

    /** @dataProvider attributesProvider
     * @param Market|mixed $market
     * @throws \ReflectionException
     */
    public function testSupportedAttributes(string $attribute, $market, bool $result): void
    {
        $marketVoter = new MarketVoter($this->mockTokenCryptoManager());
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals($result, $supportsAttributeMethod->invokeArgs($marketVoter, [$attribute, $market]));
    }

    public function voteAttributesProvider(): array
    {
        return [
            "returns valid if operate attribute, market getQuote return Token and market existed " => [
                "operate",
                true,
                true,
            ],
            "returns invalid if operate attribute, market getQuote return Token and market not existed " => [
                "operate",
                false,
                false,
                true,

            ],
            "returns valid if operate attribute, market getQuote doesn't return Token" => [
                "operate",
                false,
                true,
                false,
            ],
            "return invalid if create attribute market existed" => [
                "create",
                true,
                false,
            ],
            "return valid if create attribute market not existed" => [
                "create",
                false,
                true,
            ],
            "return valid if attribute not supported" => [
                "not_supported",
                true,
                true,
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "returns valid if attribute is supported" =>
                ["operate", $this->mockMarket(), true],

            "returns invalid if attribute is not supported" =>
                ["not_supported", $this->mockMarket(), false],

            "returns invalid if attribute is supported but market is not instance of Market" =>
                ["operate", new \stdClass(), false],
        ];
    }


    private function mockTokenCryptoManager(bool $exist = true): TokenCryptoManagerInterface
    {
        $tokenCryptoManager =  $this->createMock(TokenCryptoManagerInterface::class);
        $tokenCryptoManager->method('getByCryptoAndToken')
            ->willReturn($exist ? $this->mockTokenCrypto() : null);

        return $tokenCryptoManager;
    }

    private function mockMarket(bool $returnToken = true): Market
    {
        $market =  $this->createMock(Market::class);
        $market->method('getQuote')
            ->willReturn($returnToken ? $this->mockToken() : $this->mockTradableInterface());

        $market->method('getBase')
            ->willReturn($this->mockCrypto());

        return $market;
    }

    private function mockTokenInterface(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockTokenCrypto(): TokenCrypto
    {
        return $this->createMock(TokenCrypto::class);
    }

    private function mockTradableInterface(): TradableInterface
    {
        return $this->createMock(TradableInterface::class);
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(MarketVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
