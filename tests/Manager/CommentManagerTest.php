<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Comment;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\CommentManager;
use App\Manager\UserTokenFollowManagerInterface;
use App\Repository\CommentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommentManagerTest extends TestCase
{
    public function testGetById(): void
    {
        $commentId = 1;

        $comment = $this->mockComment();

        $commentRepository = $this->mockCommentRepository();
        $commentRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->with($commentId)
            ->willReturnOnConsecutiveCalls($comment, null);

        $commentManager = new CommentManager(
            $commentRepository,
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals($comment, $commentManager->getById($commentId));
        $this->assertNull($commentManager->getById($commentId));
    }

    public function testFindAllByCreatorAndTokenOwner(): void
    {
        $user = $this->mockUser();
        $blockedUser = $this->mockUser();
        $comments = [$this->mockcomment()];

        $commentRepository = $this->mockCommentRepository();
        $commentRepository
            ->expects($this->once())
            ->method('findAllByCreatorIdAndTokenOwnerProfileId')
            ->with($user->getId(), $blockedUser->getProfile()->getId())
            ->willReturn($comments);

        $commentManager = new CommentManager(
            $commentRepository,
            $this->mockUserTokenFollowManager(),
        );

        $this->assertEquals($comments, $commentManager->findAllByCreatorAndTokenOwner($user, $blockedUser));
    }

    public function testGetRecentCommentsByUserFeed(): void
    {
        $page = 1;
        $user = $this->mockUser();

        $token = $this->mockToken();
        $token
            ->method('getOwner')
            ->willReturn($user);

        $comments = [$this->mockComment()];

        $userTokenFollowManager = $this->mockUserTokenFollowManager();
        $userTokenFollowManager
            ->expects($this->once())
            ->method('getFollowedTokens')
            ->with($user)
            ->willReturn([$token]);

        $commentRepository = $this->mockCommentRepository();
        $commentRepository
            ->expects($this->once())
            ->method('findRecentCommentsByTokenOwners')
            ->with([$user], $page)
            ->willReturn($comments);

        $commentManager = new CommentManager(
            $commentRepository,
            $userTokenFollowManager,
        );

        $this->assertEquals($comments, $commentManager->getRecentCommentsByUserFeed($user, $page));
    }

    /** @return MockObject|Comment */
    private function mockComment(): Comment
    {
        return $this->createMock(Comment::class);
    }

    /** @return MockObject|CommentRepository */
    private function mockCommentRepository(): CommentRepository
    {
        return $this->createMock(CommentRepository::class);
    }

    private function mockUser(): User
    {
        $mock = $this->createMock(User::class);
        $mock->method('getId')->willReturn(1);
        $mock->method('getProfile')->willReturn($this->mockProfile(1));

        return $mock;
    }

    private function mockProfile(int $id): Profile
    {
        $mock = $this->createMock(Profile::class);
        $mock->method('getId')->willReturn($id);

        return $mock;
    }

    /** @return MockObject|UserTokenFollowManagerInterface*/
    private function mockUserTokenFollowManager(): UserTokenFollowManagerInterface
    {
        return $this->createMock(UserTokenFollowManagerInterface::class);
    }

    /** @return MockObject|Token */
    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }
}
