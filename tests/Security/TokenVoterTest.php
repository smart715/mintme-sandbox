<?php declare(strict_types = 1);

namespace App\Tests\Security;

use App\Entity\BlockedUser;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\BlockedUserManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Security\TokenVoter;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class TokenVoterTest extends TestCase
{
    /** @dataProvider voteAttributesProvider */
    public function testVoteOnAttribute(
        ?User $user,
        string $attribute,
        bool $result,
        ?object $subject = null,
        bool $containerParameter = false,
        bool $decision = false,
        string $balance = "1",
        string $minimumAmount = "2",
        int $tokensCount = 1,
        ?BlockedUser $blockedUser = null
    ): void {
        $voter = new TokenVoter(
            $this->mockTokenManager($balance, $tokensCount),
            $this->mockAccessDecisionManager($decision),
            $this->mockContainer($containerParameter),
            $this->mockBalanceHandler(),
            $this->mockMoneyWrapper($minimumAmount),
            $this->mockBlockedUserManager($blockedUser)
        );

        $voteOnAttributeMethod = $this->getMethod('voteOnAttribute');

        $this->assertEquals(
            $result,
            $voteOnAttributeMethod->invokeArgs(
                $voter,
                [$attribute, $subject, $this->mockTokenInterface($user)]
            )
        );
    }

    /** @dataProvider attributesProvider */
    public function testSupportedAttributes(string $attribute, ?object $subject, bool $result): void
    {
        $voter = new TokenVoter(
            $this->mockTokenManager(),
            $this->mockAccessDecisionManager(),
            $this->mockContainer(),
            $this->mockBalanceHandler(),
            $this->mockMoneyWrapper(),
            $this->createMock(BlockedUserManagerInterface::class)
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
            "user is not logged in is Invalid" => [
                "user" => null,
                "attribute" => "any-thing",
                "result" => false,
            ],
            "if subject is falsy, and user is blocked, return false" => [
                "user" => $this->mockUser(true),
                "attribute" => "create",
                "result" => false,
            ],
            "if subject is falsy, and user is not blocked, return true" => [
                "user" => $this->mockUser(false),
                "attribute" => "create",
                "result" => true,
            ],
            "if subject is Crypto, and user is not blocked, return true" => [
                "user" => $this->mockUser(false),
                "attribute" => "create",
                "result" => true,
                "subject" => $this->mockCrypto(),
            ],
            "if subject is Crypto, and user is blocked, return false" => [
                "user" => $this->mockUser(true),
                "attribute" => "create",
                "result" => false,
                "subject" => $this->mockCrypto(),
            ],
            "if attribute is create, auth_make_disable_token_creation is true and decision is true, return false" => [
                "user" => $this->mockUser(false),
                "attribute" => "create",
                "result" => true,
                "subject" => $this->mockToken(),
                "containerParameter" => true,
                "decision" => true,
            ],
            "if attribute is create, auth_make_disable_token_creation is true and decision is false, return false" => [
                "user" => $this->mockUser(false),
                "attribute" => "create",
                "result" => false,
                "subject" => $this->mockToken(),
                "containerParameter" => true,
                "decision" => false,
            ],
            "if attribute is create, auth_make_disable_token_creation is false, return true" => [
                "user" => $this->mockUser(false),
                "attribute" => "create",
                "result" => true,
                "subject" => $this->mockToken(),
                "containerParameter" => false,
            ],
            "if attribute is create, tokens count less than 5, return true" => [
                "user" => $this->mockUser(false),
                "attribute" => "create",
                "result" => true,
                "subject" => $this->mockToken(),
                "containerParameter" => false,
                "decision" => false,
                "balance" => "3",
                "minimumAmount" => "2",
                "tokensCount" => 3,
            ],
            "if attribute is create, tokens count greater than 5, return false" => [
                "user" => $this->mockUser(false),
                "attribute" => "create",
                "result" => false,
                "subject" => $this->mockToken(),
                "containerParameter" => false,
                "decision" => false,
                "balance" => "3",
                "minimumAmount" => "2",
                "tokensCount" => 6,
            ],
            "if attribute is not-blocked, and token is blocked, return false" => [
                "user" => $this->mockUser(false),
                "attribute" => "not-blocked",
                "result" => false,
                "subject" => $this->mockToken(true),
            ],
            "if attribute is not-blocked, and token isn't blocked, return true" => [
                "user" => $this->mockUser(false),
                "attribute" => "not-blocked",
                "result" => true,
                "subject" => $this->mockToken(false),
            ],
            "if attribute is edit, return false" => [
                "user" => $this->mockUser(false),
                "attribute" => "edit",
                "result" => false,
                "subject" => $this->mockToken(),
            ],
            "if attribute is delete, token is the owner, and isn't blocked, return true" => [
                "user" => $this->mockUser(false),
                "attribute" => "delete",
                "result" => true,
                "subject" => $this->mockToken(false, true),
            ],
            "if attribute is delete, token is the owner, and is blocked, return false" => [
                "user" => $this->mockUser(false),
                "attribute" => "delete",
                "result" => false,
                "subject" => $this->mockToken(true, true),
            ],
            "if attribute is delete, token isn't the owner, and isn't blocked, return false" => [
                "user" => $this->mockUser(false),
                "attribute" => "delete",
                "result" => false,
                "subject" => $this->mockToken(false, false),
            ],
            "if attribute is delete-from-wallet, and token owner is the user, return false" => [
                "user" => $this->mockUser(false, 1),
                "attribute" => "delete-from-wallet",
                "result" => false,
                "subject" => $this->mockToken(false, false, 1),
            ],
            "if attribute is delete-from-wallet, user isn't owner and got less money than the minimum, return true" => [
                "user" => $this->mockUser(false, 1),
                "attribute" => "delete-from-wallet",
                "result" => true,
                "subject" => $this->mockToken(false, false, 2),
                "containerParameter" => true,
                "decision" => true,
                "balance" => "1",
                "minimumAmount" => "2",
            ],
            "if attribute is delete-from-wallet, user isn't owner and got more money than minimum, return false" => [
                "user" => $this->mockUser(false, 1),
                "attribute" => "delete-from-wallet",
                "result" => false,
                "subject" => $this->mockToken(false, false, 2),
                "containerParameter" => true,
                "decision" => true,
                "balance" => "3",
                "minimumAmount" => "2",
            ],
            "if attribute is interact, and token owner is the user and blockedUser is null, return true" => [
                "user" => $this->mockUser(false, 1),
                "attribute" => "interact",
                "result" => true,
                "subject" => $this->mockToken(false, false, 1),
                "containerParameter" => false,
                "decision" => false,
                "balance" => "3",
                "minimumAmount" => "2",
                "tokensCount" => 1,
            ],
            "if attribute is interact, and token owner is the user and blockedUseri is not null, return false" => [
                "user" => $this->mockUser(false, 1),
                "attribute" => "interact",
                "result" => false,
                "subject" => $this->mockToken(false, false, 1),
                "containerParameter" => false,
                "decision" => false,
                "balance" => "3",
                "minimumAmount" => "2",
                "tokensCount" => 1,
                "blockedUser" => $this->mockBlockedUser(),
            ],
            "if attribute is exceed, and token owner is the user and tokens count less than 5, return false" => [
                "user" => $this->mockUser(false, 1),
                "attribute" => "exceed",
                "result" => false,
                "subject" => $this->mockToken(false, false, 1),
                "containerParameter" => false,
                "decision" => false,
                "balance" => "3",
                "minimumAmount" => "2",
                "tokensCount" => 1,
            ],
            "if attribute is exceed, and token owner is the user and tokens count greater than 5, return true" => [
                "user" => $this->mockUser(false, 1),
                "attribute" => "exceed",
                "result" => true,
                "subject" => $this->mockToken(false, false, 1),
                "containerParameter" => false,
                "decision" => true,
                "balance" => "3",
                "minimumAmount" => "2",
                "tokensCount" => 6,
            ],
            "invalid attribute, returns false" => [
                "user" => $this->mockUser(false),
                "attribute" => "invalid-attribute",
                "result" => false,
                "subject" => $this->mockToken(),
            ],
        ];
    }

    public function attributesProvider(): array
    {
        return [
            "invalid attribute, returns false" => [
                "attribute" => "invalid-attribute",
                "subject" => $this->mockToken(),
                "result" => false,
            ],
            "if valid attribute and subject is Crypto, return true" => [
                "attribute" => "create",
                "subject" => $this->mockCrypto(),
                "result" => true,
            ],
            "if valid attribute and subject is Token, return true" => [
                "attribute" => "create",
                "subject" => $this->mockToken(),
                "result" => true,
            ],
            "if valid attribute and subject is null, return true" => [
                "attribute" => "create",
                "subject" => null,
                "result" => true,
            ],
            "if valid attribute 'create' and valid subject, return true" => [
                "attribute" => "create",
                "subject" => $this->mockToken(),
                "result" => true,
            ],
            "if valid attribute 'edit' and valid subject, return true" => [
                "attribute" => "edit",
                "subject" => $this->mockToken(),
                "result" => true,
            ],
            "if valid attribute 'delete' and valid subject, return true" => [
                "attribute" => "delete",
                "subject" => $this->mockToken(),
                "result" => true,
            ],
            "if valid attribute 'delete-from-wallet' and valid subject, return true" => [
                "attribute" => "delete-from-wallet",
                "subject" => $this->mockToken(),
                "result" => true,
            ],
            "if valid attribute 'not-blocked' and valid subject, return true" => [
                "attribute" => "not-blocked",
                "subject" => $this->mockToken(),
                "result" => true,
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

    private function mockContainer(bool $containerParameter = true): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')->willReturnCallback(function ($parameter) use ($containerParameter) {
            return 'auth_make_disable_token_creation' === $parameter
                ? $containerParameter
                : 6;
        });

        return $container;
    }

    private function mockTokenManager(string $balance = "1", int $tokensCount = 1): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager->method('getRealBalance')->willReturn($this->mockBalanceResult($balance));
        $tokenManager->method('getTokensCount')->willReturn($tokensCount);

        return $tokenManager;
    }

    private function mockBalanceHandler(): BalanceHandlerInterface
    {
        return $this->createMock(BalanceHandlerInterface::class);
    }

    private function mockMoneyWrapper(string $minimumAmount = "1"): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->method('parse')->willReturn($this->dummyMoneyObject($minimumAmount));

        return $moneyWrapper;
    }

    private function mockUser(bool $isBlocked, int $id = 1): User
    {
        $user = $this->createMock(User::class);
        $user->method('isBlocked')->willReturn($isBlocked);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockToken(bool $isBlocked = false, bool $isOwner = false, int $userId = 9999): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('isBlocked')->willReturn($isBlocked);
        $token->method('isOwner')->willReturn($isOwner);
        $token->method('getOwnerId')->willReturn($userId);
        $token->method('getOwner')->willReturn($this->mockUser($isBlocked, $userId));

        return $token;
    }

    private function mockBalanceResult(string $balance = '0'): BalanceResult
    {
        $balanceResult = $this->createMock(BalanceResult::class);
        $balanceResult->method('getAvailable')->willReturn($this->dummyMoneyObject($balance));

        return $balanceResult;
    }

    private function dummyMoneyObject(string $amount = '0'): Money
    {
        return new Money($amount, new Currency('TOK'));
    }

    private function getMethod(string $methodName): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass(TokenVoter::class);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    private function mockBlockedUserManager(
        ?BlockedUser $blockedUser
    ): BlockedUserManagerInterface {
        $blockedUserManager = $this->createMock(BlockedUserManagerInterface::class);
        $blockedUserManager->method('findByBlockedUserAndOwner')->willReturn($blockedUser);

        return $blockedUserManager;
    }

    private function mockBlockedUser(): BlockedUser
    {
        return $this->createMock(BlockedUser::class);
    }
}
