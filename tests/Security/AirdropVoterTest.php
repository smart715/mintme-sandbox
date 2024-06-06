<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\User;
use App\Security\AirdropVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AirdropVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $decision,
        bool $result
    ): void {
        $voter = new AirdropVoter(
            $this->mockAccessDecisionManager($decision),
        );

        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, $this->mockAirdrop(), $this->mockTokenInterface($user)]
            )
        );
    }

    /**
     * @dataProvider attributesProvider
     * @param Airdrop|mixed $subject
     */
    public function testSupportedAttributes(string $attribute, $subject, bool $result): void
    {
        $voter = new AirdropVoter($this->mockAccessDecisionManager());
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($voter, [$attribute, $subject]),
        );
    }

    public function voteAttributesProvider(): array
    {
        return [
            "Guest user can't claim reward" => [
                null,
                "claim",
                true,
                false,
            ],

            "Valid if Access is true" => [
                $this->mockUser(),
                "claim",
                true,
                true,
            ],
            "Invalid if Access is false" => [
                $this->mockUser(),
                "claim",
                false,
                false,
            ],
            "Invalid if attribute is not supported" => [
                $this->mockUser(),
                "invalid-attribute",
                true,
                false,
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Valid attribute and Airdrop instance return true" => ["claim", $this->mockAirdrop(), true],
            "Invalid attribute and Airdrop instance return true" => ["invalid-attribute", $this->mockAirdrop(), false],
            "Valid attribute and not Airdrop instance return false" => ["claim", new \stdClass(), false],
            "Invalid attribute and not Airdrop instance return false" => ["invalid-attribute", new \stdClass(), false],
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

    private function mockAirdrop(): Airdrop
    {
        return $this->createMock(Airdrop::class);
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(AirdropVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
