<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Manager\TwoFactorManagerInterface;
use App\Security\TwoFactorVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class TwoFactorVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $result,
        bool $checkCode = false,
        bool $authMakeDisableTwofa = false,
        bool $decisionManagerDecide = false
    ): void {
        $voter = new TwoFactorVoter(
            $this->mockTwoFactorManager($checkCode),
            $this->mockDecisionManager($decisionManagerDecide),
            $this->mockContainer($authMakeDisableTwofa)
        );

        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, 'test', $this->mockTokenInterface($user)]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(?string $attribute, bool $result): void
    {
        $voter = new TwoFactorVoter(
            $this->mockTwoFactorManager(),
            $this->mockDecisionManager(),
            $this->mockContainer()
        );
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals($result, $supportsAttributeMethod->invokeArgs($voter, [$attribute, 'test']));
    }

    public function voteAttributesProvider(): array
    {
        return [
            'non google authenticated user' => [
                'user' => $this->mockUser(false),
                'attribute' => '2fa-login',
                'result' => true,
            ],
            'google authenticated user and a valid code' => [
                'user' => $this->mockUser(true),
                'attribute' => '2fa-login',
                'result' => true,
                'checkCode' => true,
            ],
            'google authenticated user and an invalid code' => [
                'user' => $this->mockUser(true),
                'attribute' => '2fa-login',
                'result' => false,
                'checkCode' => false,
            ],
            'enable 2fa returns true if it is enabled' => [
                'user' => null,
                'attribute' => '2fa-enable',
                'result' => true,
                'checkCode' => true,
            ],
            'enable 2fa returns false if authMakeDisableTwofa true and decideManager return false' => [
                'user' => null,
                'attribute' => '2fa-enable',
                'result' => false,
                'checkCode' => false,
                'authMakeDisableTwofa' => true,
            ],
            'enable 2fa returns true if authMakeDisableTwofa false and decideManager return true' => [
                'user' => null,
                'attribute' => '2fa-enable',
                'result' => true,
                'checkCode' => false,
                'authMakeDisableTwofa' => true,
                'decisionManagerDecide' => true,
            ],
            'wrong attribute, returns false' => [
                'user' => $this->mockUser(false),
                'attribute' => 'wrong-attribute',
                'result' => false,
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Right attribute returns true" => ['2fa-login', true],
            "Wrong attribute returns false" => ['wrong-attribute', false],
        ];
    }

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function mockTwoFactorManager(bool $checkCode = false): TwoFactorManagerInterface
    {
        $twoFactorManager = $this->createMock(TwoFactorManagerInterface::class);
        $twoFactorManager->method('checkCode')->willReturn($checkCode);

        return $twoFactorManager;
    }

    private function mockDecisionManager(bool $decide = false): AccessDecisionManagerInterface
    {
        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager->method('decide')->willReturn($decide);

        return $decisionManager;
    }

    private function mockContainer(bool $return = false): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')->willReturn($return);

        return $container;
    }

    private function mockUser(bool $isGoogleAuthenticatorEnabled = false): User
    {
        $user = $this->createMock(User::class);
        $user->method('isGoogleAuthenticatorEnabled')->willReturn($isGoogleAuthenticatorEnabled);

        return $user;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(TwoFactorVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
