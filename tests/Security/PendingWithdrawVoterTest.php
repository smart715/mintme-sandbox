<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\PendingWithdraw;
use App\Entity\User;
use App\Security\PendingWithdrawVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PendingWithdrawVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        string $attribute,
        ?User $user,
        User $PendingWithdrawOwner,
        bool $result
    ): void {
        $voter = new PendingWithdrawVoter();
        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, $this->mockPendingWithdraw($PendingWithdrawOwner), $this->mockTokenInterface($user)]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(string $attribute, PendingWithdraw $pendingWithdraw, bool $result): void
    {
        $voter = new PendingWithdrawVoter();
        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals(
            $result,
            $supportsAttributeMethod->invokeArgs($voter, [$attribute, $pendingWithdraw])
        );
    }

    public function voteAttributesProvider(): array
    {
        return [
            "A guest can't have pending withdraws" => [
                'edit',
                null,
                $this->mockUser(),
                false,
            ],
            "A user that is not the owner can't have pending withdraws" => [
                'edit',
                $this->mockUser(10),
                $this->mockUser(11),
                false,
            ],
            "A user that is the owner can have pending withdraws" => [
                'edit',
                $user = $this->mockUser(),
                $user,
                true,
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Right attribute" => ['edit', $this->mockPendingWithdraw(), true],
            "Wrong attribute" => ['delete', $this->mockPendingWithdraw(), false],
        ];
    }

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function mockUser(int $id = 1): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function mockPendingWithdraw(?User $user = null): PendingWithdraw
    {
        $pendingWithdraw = $this->createMock(PendingWithdraw::class);

        if ($user) {
            $pendingWithdraw->method('getUser')->willReturn($user);
        }

        return $pendingWithdraw;
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(PendingWithdrawVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
