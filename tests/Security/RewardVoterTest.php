<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\Profile;
use App\Entity\Rewards\Reward;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Security\RewardVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RewardVoterTest extends TestCase
{
    /** @dataProvider supportedAttributesProvider */
    public function testSupportedAttributes(string $attribute, ?object $subject, bool $result): void
    {
        $rewardVoter = new RewardVoter();

        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($rewardVoter, [$attribute, $subject]),
        );
    }

    /** @dataProvider voteOnAttributeProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $result,
        ?object $subject = null
    ): void {
        $rewardVoter = new RewardVoter();

        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $rewardVoter,
                [$attribute, $subject, $this->mockTokenInterface($user)]
            )
        );
    }

    public function supportedAttributesProvider(): array
    {
        $reward = $this->mockReward();

        return [
            "if attribute is not valid and subject is reward, return false" => [
                "attribute" => "invalid-attribute",
                "subject" => $reward,
                "result" => false,
            ],
            "if attribute is not valid and subject is null, return false" => [
                "attribute" => "invalid-attribute'",
                "subject" => null,
                "result" => false,
            ],
            "if attribute is 'add' and subject is reward, return true" => [
                "attribute" => "add",
                "subject" => $reward,
                "result" => true,
            ],
            "if attribute is 'edit' and subject is reward, return true" => [
                "attribute" => "edit",
                "subject" => $reward,
                "result" => true,
            ],
            "if attribute is 'delete' and subject is reward, return true" => [
                "attribute" => "delete",
                "subject" => $reward,
                "result" => true,
            ],
            "if attribute is 'accept-member' and subject is reward, return true" => [
                "attribute" => "accept-member",
                "subject" => $reward,
                "result" => true,
            ],
            "if attribute is 'add-member' and subject is reward, return true" => [
                "attribute" => "add-member",
                "subject" => $reward,
                "result" => true,
            ],
        ];
    }

    public function voteOnAttributeProvider(): array
    {
        $ownerUser = $this->mockUser(1);
        $randomUser = $this->mockUser(2);
        $profile = new Profile($ownerUser);
        $token = (new Token())->setProfile($profile);
        $reward = (new Reward())->setToken($token);

        return [
            "if attribute is 'add' with owner user should return true" => [
                "user" => $ownerUser,
                "attribute" => 'add',
                "result" => true,
                "subject" => $reward,
            ],
            "if attribute is 'add' with not owner user(random user) should return false" => [
                "user" => $randomUser,
                "attribute" => 'add',
                "result" => false,
                "subject" => $reward,
            ],
            "if attribute is 'edit' with owner user should return true" => [
                "user" => $ownerUser,
                "attribute" => 'edit',
                "result" => true,
                "subject" => $reward,
            ],
            "if attribute is 'edit' with not owner user(random user) should return false" => [
                "user" => $randomUser,
                "attribute" => 'edit',
                "result" => false,
                "subject" => $reward,
            ],
            "if attribute is 'delete' with owner user should return true" => [
                "user" => $ownerUser,
                "attribute" => 'delete',
                "result" => true,
                "subject" => $reward,
            ],
            "if attribute is 'delete' with not owner user(random user) should return false" => [
                "user" => $randomUser,
                "attribute" => 'delete',
                "result" => false,
                "subject" => $reward,
            ],
            "if attribute is 'accept-member' with owner user should return true" => [
                "user" => $ownerUser,
                "attribute" => 'accept-member',
                "result" => true,
                "subject" => $reward,
            ],
            "if attribute is 'accept-member' with not owner user(random user) should return false" => [
                "user" => $randomUser,
                "attribute" => 'accept-member',
                "result" => false,
                "subject" => $reward,
            ],
            "if attribute is 'add-member' with owner user should return false" => [
                "user" => $ownerUser,
                "attribute" => 'add-member',
                "result" => false,
                "subject" => $reward,
            ],
            "if attribute is 'add-member' with not owner user(random user) should return true" => [
                "user" => $randomUser,
                "attribute" => 'add-member',
                "result" => true,
                "subject" => $reward,
            ],
            "if attribute is invalid even with owner should return false" => [
                "user" => $ownerUser,
                "attribute" => 'invalid-attribute',
                "result" => false,
                "subject" => $reward,
            ],
            "if attribute is valid and user is null should return false" => [
                "user" => null,
                "attribute" => 'add',
                "result" => false,
                "subject" => $reward,
            ],
        ];
    }

    private function mockUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function mockReward(): Reward
    {
        return  $this->createMock(Reward::class);
    }

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $tokenInterface = $this->createMock(TokenInterface::class);
        $tokenInterface->method('getUser')->willReturn($user);

        return $tokenInterface;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(RewardVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
