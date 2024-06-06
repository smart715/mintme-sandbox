<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Market;
use App\Security\Config\DisabledServicesConfig;
use App\Security\TradingVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class TradingVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $result,
        bool $containerParameterValue = false,
        bool $decision = false,
        ?TradableInterface $object = null
    ): void {
        $voter = new TradingVoter(
            $this->mockAccessDecisionManager($decision),
            $this->createMock(DisabledServicesConfig::class),
            $this->mockContainer($containerParameterValue)
        );

        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, $this->mockMarket($object), $this->mockTokenInterface($user)]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(string $attribute, ?Market $subject, bool $result): void
    {
        $voter = new TradingVoter(
            $this->mockAccessDecisionManager(),
            $this->createMock(DisabledServicesConfig::class),
            $this->mockContainer()
        );
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($voter, [$attribute, $subject]),
        );
    }

    public function voteAttributesProvider(): array
    {
        return [
            "Guest user can't make order" => [
                "user" => null,
                "attribute" => "make-order",
                "result" => false,
            ],
            "Guest user can't sell order" => [
                "user" => null,
                "attribute" => "sell-order",
                "result" => false,
            ],
            "User can make order if container parameter 'auth_make_disable_sell' is false" => [
                "user" => $this->mockUser(),
                "attribute" => "make-order",
                "result" => true,
                "containerParameterValue" => false,
            ],
            "User can't make order if container parameter 'auth_make_disable_sell' is true and decision is false" => [
                "user" => $this->mockUser(),
                "attribute" => "make-order",
                "result" => false,
                "containerParameterValue" =>true,
                "decision" => false,
            ],
            "User can make order if container parameter 'auth_make_disable_sell' is true and decision is true" => [
                "user" => $this->mockUser(),
                "attribute" => "make-order",
                "result" => true,
                "containerParameterValue" => true,
                "decision" => true,
            ],
            "User can sell if tradable is crypto and 'auth_make_disable_sell' is false" => [
                "user" => $this->mockUser(),
                "attribute" => "sell-order",
                "result" => true,
                "containerParameterValue" => false,
                "decision" => true,
                "object" => $this->mockCrypto(),
            ],
            "User can't sell if tradable is crypto and 'auth_make_disable_sell' is true and decision is false" => [
                "user" => $this->mockUser(),
                "attribute" => "sell-order",
                "result" => false,
                "containerParameterValue" => true,
                "decision" => false,
                "object" => $this->mockCrypto(),
            ],
            "User can sell if tradable is crypto and 'auth_make_disable_sell' is true and decision is true" => [
                "user" => $this->mockUser(),
                "attribute" => "sell-order",
                "result" => true,
                "containerParameterValue" => true,
                "decision" => true,
                "object" => $this->mockCrypto(),
            ],
            "User can sell if tradable is token and he is the owner" => [
                "user" => $user = $this->mockUser(10),
                "attribute" => "sell-order",
                "result" => true,
                "containerParameterValue" => false,
                "decision" => false,
                "object" => $this->mockToken($user),
            ],
            "User can sell if tradable is token, isn't the owner, 'auth_make_disable_sell' & decision are true" => [
                "user" => $this->mockUser(10),
                "attribute" => "sell-order",
                "result" => true,
                "containerParameterValue" => true,
                "decision" => true,
                "object" => $this->mockToken($this->mockUser(11)),
            ],
            "User can't sell if tradable is token, isn't the owner, 'auth_make_disable_sell' & decision are false" => [
                "user" => $this->mockUser(10),
                "attribute" => "sell-order",
                "result" => false,
                "containerParameterValue" => true,
                "decision" => false,
                "object" => $this->mockToken($this->mockUser(11)),
            ],
            "User can sell if tradable is token, isn't the owner, 'auth_make_disable_sell' is false" => [
                "user" => $this->mockUser(10),
                "attribute" => "sell-order",
                "result" => true,
                "containerParameterValue" => false,
                "decision" => true,
                "object" => $this->mockToken($this->mockUser(11)),
            ],
            "User can't sell if tradable token, not owner, 'auth_make_disable_sell' is true and decision is false" => [
                "user" => $this->mockUser(10),
                "attribute" => "sell-order",
                "result" => false,
                "containerParameterValue" => true,
                "decision" => false,
                "object" => $this->mockToken($this->mockUser(11)),
            ],
            "Wrong attribute, returns false" => [
                "user" => $this->mockUser(),
                "attribute" => "wrong-attribute",
                "result" => false,
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Right attribute make-order with Market instance is valid" => [
                "attribute" => "make-order",
                "subject" => $this->mockMarket(),
                "result" => true,
            ],
            "Right attribute sell-order with Market instance is valid" => [
                "attribute" => "sell-order",
                "subject" => $this->mockMarket(),
                "result" => true,
            ],
            "Right attribute without Market instance is invalid" => [
                "attribute" => "sell-order",
                "subject" => null,
                "result" => false,
            ],
            "Wrong attribute is invalid" => [
                "attribute" => "wrong-attribute",
                "subject" => $this->mockMarket(),
                "result" => false,
            ],
        ];
    }

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $tokenInterface = $this->createMock(TokenInterface::class);
        $tokenInterface->method('getUser')->willReturn($user);

        return $tokenInterface;
    }

    private function mockAccessDecisionManager(bool $decision = true): AccessDecisionManagerInterface
    {
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager->method('decide')->willReturn($decision);

        return $accessDecisionManager;
    }

    private function mockContainer(bool $containerParameterValue = true): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')
            ->willReturn($containerParameterValue);

        return $container;
    }

    private function mockMarket(?TradableInterface $tradable = null): Market
    {
        $market = $this->createMock(Market::class);

        if ($tradable) {
            $market->method('getQuote')->willReturn($tradable);
        }

        return $market;
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockToken(?User $tokenOwner): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getOwner')->willReturn($tokenOwner);

        return $token;
    }

    private function mockUser(int $id = 1): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(TradingVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
