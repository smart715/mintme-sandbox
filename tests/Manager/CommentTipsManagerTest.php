<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Comment;
use App\Entity\CommentTip;
use App\Entity\User;
use App\Manager\CommentTipsManager;
use App\Repository\CommentTipRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommentTipsManagerTest extends TestCase
{
    public function testGetByUserAndComment(): void
    {
        $user = $this->mockUser();
        $comment = $this->mockComment();
        $commentTip = $this->mockCommentTip();

        $commentTipRepository = $this->mockCommentTipRepository();
        $commentTipRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['user' => $user, 'comment' => $comment])
            ->willReturn($commentTip, null);

        $commentTipsManager = new CommentTipsManager($commentTipRepository);

        $this->assertEquals($commentTip, $commentTipsManager->getByUserAndComment($user, $comment));
        $this->assertNull($commentTipsManager->getByUserAndComment($user, $comment));
    }

    /** @return MockObject|Comment */
    private function mockComment(): Comment
    {
        return $this->createMock(Comment::class);
    }

    /** @return MockObject|User */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    /** @return MockObject|CommentTip */
    private function mockCommentTip(): CommentTip
    {
        return $this->createMock(CommentTip::class);
    }

    /** @return MockObject|CommentTipRepository */
    private function mockCommentTipRepository(): CommentTipRepository
    {
        return $this->createMock(CommentTipRepository::class);
    }
}
