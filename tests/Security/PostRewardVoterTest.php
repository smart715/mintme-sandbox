<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\PostRewardVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class PostRewardVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $result,
        bool $containerParameter = false,
        bool $decision = false
    ): void {
        $voter = new PostRewardVoter(
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
        $voter = new PostRewardVoter($this->mockAccessDecisionManager(), $this->mockContainer());
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
                "collect-reward",
                false,
            ],
            "Valid if container parameter 'auth_make_disable_post_reward' is false" => [
                new User(),
                "collect-reward",
                true,
                false,
            ],
            "Valid if container parameter 'auth_make_disable_post_reward' is false and Access is true" => [
                new User(),
                "collect-reward",
                true,
                false,
                true,
            ],
            "Invalid if container parameter 'auth_make_disable_post_reward' is true and Access is false" => [
                new User(),
                "collect-reward",
                false,
                true,
                false,
            ],
            "Valid if container parameter 'auth_make_disable_post_reward' is true and Access is true" => [
                new User(),
                "collect-reward",
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
            "Valid attribute return true" => ["collect-reward", true],
            "Invalid attribute return true" => ["invalid-attribute", false],
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

    private function mockContainer(bool $authMakeDisablePostReward = true): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')
            ->with('auth_make_disable_post_reward')
            ->willReturn($authMakeDisablePostReward);

        return $container;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(PostRewardVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
