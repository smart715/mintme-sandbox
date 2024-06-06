<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserVoterTest extends TestCase
{
    /** @dataProvider supportedAttributesProvider */
    public function testSupportedAttributes(string $attribute, ?object $subject, bool $result): void
    {
        $userVoter = new UserVoter();

        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($userVoter, [$attribute, $subject]),
        );
    }

    /** @dataProvider voteOnAttributeProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $result,
        ?object $subject = null
    ): void {
        $userVoter = new UserVoter();

        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $userVoter,
                [$attribute, $subject, $this->mockTokenInterface($user)]
            )
        );
    }

    public function supportedAttributesProvider(): array
    {
        return [
            "if attribute is not valid and subject is user, return false" => [
                "attribute" => "invalid-attribute",
                "subject" => $this->mockUser(),
                "result" => false,
            ],
            "if attribute is not valid and subject is null, return false" => [
                "attribute" => "invalid-attribute'",
                "subject" => null,
                "result" => false,
            ],
            "if attribute is valid and subject is null, return false" => [
                "attribute" => "not-blocked",
                "subject" => null,
                "result" => false,
            ],
            "if attribute is valid and subject is user, return true" => [
                "attribute" => "not-blocked",
                "subject" => $this->mockUser(),
                "result" => true,
            ],
        ];
    }

    public function voteOnAttributeProvider(): array
    {
        return [
            "if attribute is not valid, and user is not logged in, return false" => [
                "user" => null,
                "attribute" => "invalid-attribute'",
                "result" => false,
            ],
            "if attribute is not valid, and user is not blocked, return false" => [
                "user" => $this->mockUser(),
                "attribute" => "invalid-attribute'",
                "result" => false,
            ],
            "if attribute is not-blocked, and user is blocked, return false" => [
                "user" => $this->mockUser(true),
                "attribute" => "not-blocked",
                "result" => false,
            ],
            "if attribute is not-blocked, and user is not blocked, return true" => [
                "user" => $this->mockUser(),
                "attribute" => "not-blocked",
                "result" => true,
            ],
        ];
    }

    private function mockUser(bool $isBlocked = false): User
    {
        $user = $this->createMock(User::class);
        $user->method('isBlocked')->willReturn($isBlocked);

        return $user;
    }

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $tokenInterface = $this->createMock(TokenInterface::class);
        $tokenInterface->method('getUser')->willReturn($user);

        return $tokenInterface;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(UserVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
