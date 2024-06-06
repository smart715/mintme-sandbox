<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\Crypto;
use App\Security\Config\DisabledServicesConfig;
use App\Security\DisabledServicesVoter;
use App\Utils\Symbols;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DisabledServicesVoterTest extends TestCase
{
    /**
     * @dataProvider voteAttributesProvider
     */
    public function testVoteOnAttribute(string $attribute, ?Crypto $subject, string $methodName, bool $methodResult, bool $result): void
    {
        $voter = new DisabledServicesVoter($this->mockDisabledServices($methodName, $methodResult));
        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, $subject, $this->mockTokenInterface()]
            )
        );
    }

    /**
     * @dataProvider attributesProvider
     */
    public function testSupportedAttributes(string $attribute, bool $result): void
    {
        $voter = new DisabledServicesVoter($this->mockDisabledServices());
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($voter, [$attribute, new \stdClass()])
        );
    }

    public function voteAttributesProvider(): array
    {
        $attributes = [
            "If any attribute got used and method isAllServicesDisabled return true, then return false" => [
                'attribute' => 'any_attribute',
                'subject' => null,
                'methodName' => 'isAllServicesDisabled',
                'methodResult' => true,
                'result' => false,
            ],
            "If attribute is coin_deposit and method isCoinDepositsDisabled return false, then return true" => [
                'attribute' => 'coin-deposit',
                'subject' => null,
                'methodName' => 'isCoinDepositsDisabled',
                'methodResult' => false,
                'result' => true,
            ],
            "If attribute is deposit and method isCoinDepositsDisabled return true, then return false" => [
                'attribute' => 'coin-deposit',
                'subject' => null,
                'methodName' => 'isCoinDepositsDisabled',
                'methodResult' => true,
                'result' => false,
            ],
            "If attribute is withdraw and method isCoinWithdrawalsDisabled return false, then return true" => [
                'attribute' => 'coin-withdraw',
                'subject' => null,
                'methodName' => 'isCoinWithdrawalsDisabled',
                'methodResult' => false,
                'result' => true,
            ],
            "If attribute is withdraw and method isCoinWithdrawalsDisabled return true, then return false" => [
                'attribute' => 'coin-withdraw',
                'subject' => null,
                'methodName' => 'isCoinWithdrawalsDisabled',
                'methodResult' => true,
                'result' => false,
            ],
            "If attribute is token-deposit and method isTokenDepositsDisabled return false, then return true" => [
                'attribute' => 'token-deposit',
                'subject' => null,
                'methodName' => 'isTokenDepositsDisabled',
                'methodResult' => false,
                'result' => true,
            ],
            "If attribute is token-deposit and method isTokenDepositsDisabled return true, then return false" => [
                'attribute' => 'token-deposit',
                'subject' => null,
                'methodName' => 'isTokenDepositsDisabled',
                'methodResult' => true,
                'result' => false,
            ],
            "If attribute is token-withdraw and method isTokenWithdrawalsDisabled return false, then return true" => [
                'attribute' => 'token-withdraw',
                'subject' => null,
                'methodName' => 'isTokenWithdrawalsDisabled',
                'methodResult' => false,
                'result' => true,
            ],
            "If attribute is token-withdraw and method isTokenWithdrawalsDisabled return true, then return false" => [
                'attribute' => 'token-withdraw',
                'subject' => null,
                'methodName' => 'isTokenWithdrawalsDisabled',
                'methodResult' => true,
                'result' => false,
            ],
            "If attribute is deploy and method isDeployDisabled return false, then return true" => [
                'attribute' => 'deploy',
                'subject' => null,
                'methodName' => 'isDeployDisabled',
                'methodResult' => false,
                'result' => true,
            ],
            "If attribute is deploy and method isDeployDisabled return true, then return false" => [
                'attribute' => 'deploy',
                'subject' => null,
                'methodName' => 'isDeployDisabled',
                'methodResult' => true,
                'result' => false,
            ],
            "If attribute is new-trades and method isNewTradesDisabled return false, then return true" => [
                'attribute' => 'new-trades',
                'subject' => null,
                'methodName' => 'isNewTradesDisabled',
                'methodResult' => false,
                'result' => true,
            ],
            "If attribute is new-trades and method isNewTradesDisabled return true, then return false" => [
                'attribute' => 'new-trades',
                'subject' => null,
                'methodName' => 'isNewTradesDisabled',
                'methodResult' => true,
                'result' => false,
            ],
            "If attribute is trading and method isTradingDisabled return false, then return true" => [
                'attribute' => 'trading',
                'subject' => null,
                'methodName' => 'isTradingDisabled',
                'methodResult' => false,
                'result' => true,
            ],
            "If attribute is trading and method isTradingDisabled return true, then return false" => [
                'attribute' => 'trading',
                'subject' => null,
                'methodName' => 'isTradingDisabled',
                'methodResult' => true,
                'result' => false,
            ],
        ];

        $cryptoSymbols = [Symbols::BTC, Symbols::ETH, Symbols::BNB, Symbols::CRO, Symbols::WEB, Symbols::USDC];

        foreach ($cryptoSymbols as $cryptoSymbol) {
            $attributes["If attribute is coin-deposit and method isCoinDepositsDisabled($cryptoSymbol) return false, then return true"] = [
                'attribute' => 'coin-deposit',
                'subject' => $this->mockCrypto($cryptoSymbol),
                'methodName' => 'isCryptoDepositDisabled',
                'methodResult' => false,
                'result' => true,
            ];

            $attributes["If attribute is coin-deposit and method isCoinDepositsDisabled($cryptoSymbol) return true, then return false"] = [
                'attribute' => 'coin-deposit',
                'subject' => $this->mockCrypto($cryptoSymbol),
                'methodName' => 'isCryptoDepositDisabled',
                'methodResult' => true,
                'result' => false,
            ];

            $attributes["If attribute is coin-withdraw and method isCoinWithdrawalsDisabled($cryptoSymbol) return false, then return true"] = [
                'attribute' => 'coin-withdraw',
                'subject' => $this->mockCrypto($cryptoSymbol),
                'methodName' => 'isCryptoWithdrawalDisabled',
                'methodResult' => false,
                'result' => true,
            ];

            $attributes["If attribute is coin-withdraw and method isCoinWithdrawalsDisabled($cryptoSymbol) return true, then return false"] = [
                'attribute' => 'coin-withdraw',
                'subject' => $this->mockCrypto($cryptoSymbol),
                'methodName' => 'isCryptoWithdrawalDisabled',
                'methodResult' => true,
                'result' => false,
            ];
        }

        return $attributes;
    }

    public function attributesProvider(): array
    {
        return [
            "Supported coin-deposit attribute return true" => ["coin-deposit", true],
            "Supported coin-withdraw attribute return true" => ["coin-withdraw", true],
            "Supported token-deposit attribute return true" => ["token-deposit", true],
            "Supported token-withdraw attribute return true" => ["token-withdraw", true],
            "Supported deploy attribute return true" => ["deploy", true],
            "Supported new-trades attribute return true" => ["new-trades", true],
            "Supported trading attribute return true" => ["trading", true],
            "Unsupported attribute return false" => ["unsupported", false],
        ];
    }

    private function mockTokenInterface(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    private function mockDisabledServices(?string $method = null, bool $methodResult = false): DisabledServicesConfig
    {
        $disabledServices = $this->createMock(DisabledServicesConfig::class);

        $disabledServices->method('isAllServicesDisabled')->willReturn(false);

        if ($method) {
            $disabledServices->method($method)->willReturn($methodResult);
        }

        return $disabledServices;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(DisabledServicesVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }
}
