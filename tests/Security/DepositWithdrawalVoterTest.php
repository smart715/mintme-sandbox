<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\DepositWithdrawalVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class DepositWithdrawalVoterTest extends TestCase
{
    private DepositWithdrawalVoter $voter;

    public function setUp(): void
    {
        $this->voter = new DepositWithdrawalVoter(
            $this->createMock(AccessDecisionManagerInterface::class),
            $this->createMock(ContainerInterface::class)
        );
    }

    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $result,
        bool $containerParameter = false,
        bool $decision = false
    ): void {
        $voter = new DepositWithdrawalVoter(
            $this->mockAccessDecisionManager($decision),
            $this->mockContainer($containerParameter)
        );

        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, new \stdClass(), $this->mockTokenInterface($user)]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(string $attribute, bool $result): void
    {
        $voter = new DepositWithdrawalVoter($this->mockAccessDecisionManager(), $this->mockContainer());
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($voter, [$attribute, new \stdClass()]),
        );
    }

    public function voteAttributesProvider(): array
    {
        return [
            "Guest user can't deposit participate" => [
                null,
                "make-deposit",
                false,
            ],
            "Guest user can't withdraw participate" => [
                null,
                "make-withdrawal",
                false,
            ],
            "User can deposit if container parameter returns false" => [
                new User(),
                "make-deposit",
                true,
                false,
            ],
            "User can withdraw if container parameter returns false" => [
                new User(),
                "make-withdrawal",
                true,
                false,
            ],
            "User can't withdraw if container parameter returns true and Access is false" => [
                new User(),
                "make-withdraw",
                false,
                true,
                false,
            ],
            "User can't deposit if container parameter returns true and Access is false" => [
                new User(),
                "make-deposit",
                false,
                true,
                false,
            ],
            "User can deposit if container parameter returns true and Access is true" => [
                new User(),
                "make-deposit",
                true,
                true,
                true,
            ],
            "User can withdraw if container parameter returns true and Access is true" => [
                new User(),
                "make-withdrawal",
                true,
                true,
                true,
            ],
            "Invalid if attribute is not supported" => [
                new User(),
                "invalid-attribute",
                false,
            ],

        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Valid attribute make-withdrawal returns true" => ["make-withdrawal", true],
            "Valid attribute make-deposit returns true" => ["make-deposit", true],
            "Invalid attribute returns true" => ["invalid-attribute", false],
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

    private function mockContainer(bool $parameterValue = false): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')
            ->willReturn($parameterValue);

        return $container;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(DepositWithdrawalVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
    public function testConfirmWithdrawalBlockedUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(User::class);
        $user->method('isBlocked')->willReturn(true);
        
        $token->method('getUser')->willReturn($user);

        $actual = $this->callNonPublicMethod(
            $this->voter,
            'voteOnAttribute',
            ['confirm-withdrawal', 'some-subject', $token]
        );

        $expected = false;

        $this->assertSame(
            $expected,
            $actual
        );
    }

    private function callNonPublicMethod(object $obj, string $name, array $args): bool
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
