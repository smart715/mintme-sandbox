<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\PostManager;
use App\Manager\TokenManagerInterface;
use App\Manager\UserTokenFollowManagerInterface;
use App\Repository\PostRepository;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PostManagerTest extends TestCase
{
    public function testGetRepository(): void
    {
        $postRepository = $this->mockPostRepository();

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals($postRepository, $postManager->getRepository());
    }

    public function testGetById(): void
    {
        $post = $this->mockPost();

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($post);

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals($post, $postManager->getById(1));
    }

    public function testGetBySlug(): void
    {
        $post = $this->mockPost();

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'slug' => 'TEST',
                'status' => POST::STATUS_ACTIVE,
            ])
            ->willReturn($post);

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals($post, $postManager->getBySlug('TEST'));
    }

    public function testGetPostsCreatedAt(): void
    {
        $post = $this->mockPost();

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('getPostsCreatedAt')
            ->with(new \DateTimeImmutable('2022-10-18'))
            ->willReturn([$post]);

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals(
            [$post],
            $postManager->getPostsCreatedAt(new \DateTimeImmutable('2022-10-18'))
        );
    }

    public function testGetPostsCreatedAtByToken(): void
    {
        $post = $this->mockPost();

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('getPostsCreatedAtByToken')
            ->with(
                $this->mockToken(),
                new \DateTimeImmutable('2022-10-18')
            )->willReturn([$post]);

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals(
            [$post],
            $postManager->getPostsCreatedAtByToken(
                $this->mockToken(),
                new \DateTimeImmutable('2022-10-18')
            )
        );
    }

    public function testGetPostsByToken(): void
    {
        $post = $this->mockPost();
        $token = $this->mockToken();
        $limit = 10;
        $offset = 0;

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'token' => $token,
                    'status' => POST::STATUS_ACTIVE,
                ],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            )->willReturn([$post]);

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals(
            [$post],
            $postManager->getPostsByToken($token, $offset, $limit)
        );
    }

    public function testGetRecentPostByUserFeedWithQuietToken(): void
    {
        $user = $this->mockUser();

        $tokenManager = $this->mockTokenManager();
        $balanceHandler = $this->mockBalanceHandler();
        $userTokenFollowManager = $this->mockUserTokenFollowManager();

        $token1 = $this->mockToken();
        $token1->method('isQuiet')->willReturn(true);

        $token2 = $this->mockToken();
        $token2->method('isQuiet')->willReturn(false);

        $userTokenFollowManager
            ->method('getFollowedTokens')
            ->with($user)
            ->willReturn([$token1, $token2]);

        $balanceHandler
            ->expects($this->once())
            ->method('balance')
            ->willReturn($this->mockBalanceResult());

        $balanceResult = $this->mockBalanceResult();
        $balanceResult
            ->expects($this->once())
            ->method('getAvailable')
            ->willReturn(new Money(10, new Currency(Symbols::TOK)));

        $tokenManager
            ->expects($this->once())
            ->method('getRealBalance')
            ->with($token2, $balanceResult, $user)
            ->willReturn($balanceResult);

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('findRecentPostsByTokens')
            ->with([$token2], 1)
            ->willReturn(['TEST']);

        $postManager = new PostManager(
            $postRepository,
            $tokenManager,
            $balanceHandler,
            $userTokenFollowManager,
        );

        $result = $postManager->getRecentPostsByUserFeed($user, 1);

        $this->assertEquals(['TEST'], $result);
    }

    public function testGetRecentPostByUserFeedTokenBalanceNegative(): void
    {
        $user = $this->mockUser();

        $tokenManager = $this->mockTokenManager();
        $userTokenFollowManager = $this->mockUserTokenFollowManager();

        $token1 = $this->mockToken();
        $token1->method('isQuiet')->willReturn(false);

        $token2 = $this->mockToken();
        $token2->method('isQuiet')->willReturn(false);

        $userTokenFollowManager
            ->method('getFollowedTokens')
            ->with($user)
            ->willReturn([$token1, $token2]);

        $balanceHandler = $this->mockBalanceHandler();

        $balanceHandler
            ->expects($this->exactly(2))
            ->method('balance')
            ->willReturn($this->mockBalanceResult());

        $balanceResult = $this->mockBalanceResult();
        $balanceResult
            ->expects($this->exactly(2))
            ->method('getAvailable')
            ->willReturnOnConsecutiveCalls(
                new Money(-10, new Currency(Symbols::TOK)),
                new Money(10, new Currency(Symbols::TOK))
            );

        $tokenManager
            ->expects($this->exactly(2))
            ->method('getRealBalance')
            ->withConsecutive(
                [$token1, $balanceResult, $user],
                [$token2, $balanceResult, $user]
            )->willReturn($balanceResult);

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('findRecentPostsByTokens')
            ->with([$token2], 1)
            ->willReturn(['TEST']);

        $postManager = new PostManager(
            $postRepository,
            $tokenManager,
            $balanceHandler,
            $userTokenFollowManager,
        );

        $result = $postManager->getRecentPostsByUserFeed($user, 1);

        $this->assertEquals(['TEST'], $result);
    }

    public function testGetTokenPostsCount(): void
    {
        $token = $this->mockToken();
        $count = 1;

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('count')
            ->with([
                'token' => $token,
                'status' => POST::STATUS_ACTIVE,
            ])
            ->willReturn($count);

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals($count, $postManager->getTokenPostsCount($token));
    }

    public function testGetActivePostsByToken(): void
    {
        $post = $this->mockPost();
        $token = $this->mockToken();
        $offset = 0;
        $limit =  10;

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('getActivePostsByToken')
            ->with($token, $offset, $limit)
            ->willReturn([$post]);

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals([$post], $postManager->getActivePostsByToken($token, $offset, $limit));
    }

    public function testGetActivePostsCountByToken(): void
    {
        $token = $this->mockToken();
        $count = 1;

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('count')
            ->with([
                'token' => $token,
                'status' => POST::STATUS_ACTIVE,
            ])
            ->willReturn($count);

        $postManager = new PostManager(
            $postRepository,
            $this->mockTokenManager(),
            $this->mockBalanceHandler(),
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals($count, $postManager->getActivePostsCountByToken($token));
    }

    /**
     * @return PostRepository|MockObject
     */
    private function mockPostRepository()
    {
        return $this->createMock(PostRepository::class);
    }

    /**
     * @return Token|MockObject
     */
    private function mockToken()
    {
        return $this->createMock(Token::class);
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    /**
     * @return Post|MockObject
     */
    private function mockPost()
    {
        return $this->createMock(Post::class);
    }

    /**
     * @return TokenManagerInterface|MockObject
     */
    private function mockTokenManager()
    {
        return $this->createMock(TokenManagerInterface::class);
    }

    /**
     * @return BalanceHandlerInterface|MockObject
     */
    private function mockBalanceHandler()
    {
        return $this->createMock(BalanceHandlerInterface::class);
    }

    /**
     * @return BalanceResult|MockObject
     */
    private function mockBalanceResult(): BalanceResult
    {
        return $this->createMock(BalanceResult::class);
    }

    /**
     * @return UserTokenFollowManagerInterface|MockObject
     */
    private function mockUserTokenFollowManager(): UserTokenFollowManagerInterface
    {
        return $this->createMock(UserTokenFollowManagerInterface::class);
    }
}
