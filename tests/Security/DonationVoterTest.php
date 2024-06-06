<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\DonationVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class DonationVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $result,
        bool $containerParameter = false,
        bool $decision = false
    ): void {
        $voter = new DonationVoter(
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
        $voter = new DonationVoter(
            $this->mockAccessDecisionManager(),
            $this->mockContainer()
        );
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($voter, [$attribute, new \stdClass()]),
        );
    }

    public function voteAttributesProvider(): array
    {
        return [
            "Guest user can't participate" => [
                null,
                "make-donation",
                false,
            ],
            "Valid if container parameter 'auth_make_disable_donations' is false" => [
                new User(),
                "make-donation",
                true,
                false,
            ],
            "Valid if container parameter 'auth_make_disable_donations' is false and Access is true" => [
                new User(),
                "make-donation",
                true,
                false,
                true,
            ],
            "Invalid if container parameter 'auth_make_disable_donations' is true and Access is false" => [
                new User(),
                "make-donation",
                false,
                true,
                false,
            ],
            "Valid if container parameter 'auth_make_disable_donations' is true and Access is true" => [
                new User(),
                "make-donation",
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

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $tokenInterface = $this->createMock(TokenInterface::class);
        $tokenInterface->method('getUser')->willReturn($user);

        return $tokenInterface;
    }

    public function attributesProvider(): array
    {
        return [
            "Valid attribute return true" => ["make-donation", true],
            "Invalid attribute return true" => ["invalid-attribute", false],
        ];
    }

    private function mockAccessDecisionManager(bool $decision = true): AccessDecisionManagerInterface
    {
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager->method('decide')->willReturn($decision);

        return $accessDecisionManager;
    }

    private function mockContainer(bool $authMakeDisablePostReward = true): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')
            ->with('auth_make_disable_donations')
            ->willReturn($authMakeDisablePostReward);

        return $container;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(DonationVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
