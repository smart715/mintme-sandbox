<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\Post;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\TokenManagerInterface;
use App\Security\PostVoter;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PostVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        ?User $user,
        User $owner,
        string $attribute,
        bool $result,
        string $balance = "0",
        string $amount = "0"
    ): void {
        $postVoter = new PostVoter(
            $this->mockTokenManager($balance),
            $this->mockBalanceHandler(),
            $this->mockLogger()
        );

        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $postVoter,
                [$attribute, $this->mockPost($owner, $amount), $this->mockTokenInterface($user)]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(?string $attribute, ?Post $post, bool $result): void
    {
        $postVoter = new PostVoter(
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockLogger()
        );

        $supportsAttributeMethod = $this->getMethod('supports');

        $this->assertEquals($result, $supportsAttributeMethod->invokeArgs($postVoter, [$attribute, $post]));
    }

    public function voteAttributesProvider(): array
    {
        return [
            "Owner can view post" => [
                $user = $this->mockUser(),
                $user,
                'view',
                true,
            ],
            "User can view post if balance is greater than amount" => [
                $this->mockUser(),
                $this->mockUser(),
                'view',
                true,
                "2",
                "1",
            ],
            "User can view post if balance is equal to amount" => [
                $this->mockUser(),
                $this->mockUser(),
                'view',
                true,
                "1",
                "1",
            ],
            "User can't view post if balance is smaller than amount" => [
                $this->mockUser(),
                $this->mockUser(),
                'view',
                false,
                "2",
                "3",
            ],
            "User can view post if amount equal to zero" => [
                $this->mockUser(),
                $this->mockUser(),
                'view',
                true,
                "0",
                "0",
            ],
            "Guest can view post if amount is 0" => [
                null,
                $this->mockUser(),
                'view',
                true,
                "0",
                "0",
            ],
            "Guest can't view post if amount is bigger than 0" => [
                null,
                $this->mockUser(),
                'view',
                false,
                "0",
                "1",
            ],
            "Owner can edit the post" => [
                $owner = $this->mockUser(),
                $owner,
                'edit',
                true,
            ],
            "User can't edit a post he didn't create" => [
                $this->mockUser(),
                $this->mockUser(),
                'edit',
                false,
            ],
            "Invalid attribute, returns false" => [
                $this->mockUser(),
                $this->mockUser(),
                'invalid-attribute',
                false,
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "Right attribute view and post is supported" => ["view", $this->mockPost(), true],
            "Right attribute edit and post is supported" => ["edit", $this->mockPost(), true],
            "Right attribute and no post is not supported" => ["edit", null, false],
            "Wrong attribute and post is not supported" => ["junk", $this->mockPost(), false],
            "Wrong attribute and no post is not supported" => ["junk", null, false],
        ];
    }

    private function mockTokenInterface(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    # Token DO NOT implement TokenInterface, so we have to mock it
    private function mockToken(?User $user): Token
    {
        $token = $this->createMock(Token::class);

        if ($user) {
            $token->method('getOwner')->willReturn($user);
        } else {
            $token->method('getOwner')->willReturn($this->mockUser());
        }

        return $token;
    }

    private function mockPost(?User $user = null, string $amount = '0'): Post
    {
        $post = $this->createMock(Post::class);

        if ($user) {
            $post->method('getToken')->willReturn($this->mockToken($user));
            $post->method('getAuthor')->willReturn($this->mockProfile($user));
        }

        $post->method('getAmount')->willReturn($this->dummyMoneyObject($amount));

        return $post;
    }

    private function mockTokenManager(string $balance = "0"): tokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager->method('getRealBalance')->willReturn($this->mockBalanceResult($balance));

        return $tokenManager;
    }

    private function mockBalanceHandler(): BalanceHandlerInterface
    {
        return $this->createMock(BalanceHandlerInterface::class);
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockProfile(User $user): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($user);

        return $profile;
    }

    private function mockBalanceResult(string $balance = '0'): BalanceResult
    {
        $balanceResult = $this->createMock(BalanceResult::class);
        $balanceResult->method('getFullAvailable')->willReturn($this->dummyMoneyObject($balance));

        return $balanceResult;
    }

    private function dummyMoneyObject(string $amount = '0'): Money
    {
        return new Money($amount, new Currency('TOK'));
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(PostVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
