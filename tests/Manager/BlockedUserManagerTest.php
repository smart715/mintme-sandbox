<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\BlockedUser;
use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Voting\TokenVoting;
use App\Manager\BlockedUserManager;
use App\Manager\CommentManagerInterface;
use App\Manager\VotingManagerInterface;
use App\Repository\BlockedUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BlockedUserManagerTest extends TestCase
{
    public function testBlockUserWithoutRemoveActions(): void
    {
        $repo = $this->createMock(BlockedUserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $commentManager = $this->createMock(CommentManagerInterface::class);
        $votingManager = $this->createMock(VotingManagerInterface::class);

        $userToBlock = new User();
        $user = new User();
        $blockedUser = new BlockedUser($user, $userToBlock);

        $em
            ->expects($this->once())
            ->method('persist')
            ->with($blockedUser);

        $em
            ->expects($this->once())
            ->method('flush');

        $blockedUserManager = new BlockedUserManager(
            $repo,
            $em,
            $commentManager,
            $votingManager
        );

        $blockedUserManager->blockUser($user, $userToBlock);
    }

    public function testBlockUserWithRemoveActions(): void
    {
        $repo = $this->createMock(BlockedUserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $commentManager = $this->createMock(CommentManagerInterface::class);
        $votingManager = $this->createMock(VotingManagerInterface::class);

        $userToBlock = new User();
        $user = new User();

        $tokenVotings = [
            new TokenVoting(),
            new TokenVoting(),
        ];

        $votingManager
            ->expects($this->once())
            ->method('getAllCreatedByUserAndTokenOwner')
            ->with($userToBlock, $user)
            ->willReturn($tokenVotings);

        $comments = [
            new Comment(),
            new Comment(),
        ];

        $commentManager->expects($this->once())
            ->method('findAllByCreatorAndTokenOwner')
            ->willReturn($comments);

        $em
            ->expects($this->exactly(4))
            ->method('remove')
            ->withConsecutive(
                [$tokenVotings[0]],
                [$tokenVotings[1]],
                [$comments[0]],
                [$comments[1]]
            );

        $blockedUser = new BlockedUser($user, $userToBlock);

        $em
            ->expects($this->once())
            ->method('persist')
            ->with($blockedUser);

        $em
            ->expects($this->once())
            ->method('flush');


        $blockedUserManager = new BlockedUserManager(
            $repo,
            $em,
            $commentManager,
            $votingManager
        );

        $blockedUserManager->blockUser($user, $userToBlock, true);
    }

    public function testUnblockUser(): void
    {
        $repo = $this->createMock(BlockedUserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $commentManager = $this->createMock(CommentManagerInterface::class);
        $votingManager = $this->createMock(VotingManagerInterface::class);

        $userToBlock = new User();
        $user = new User();
        $blockedUser = new BlockedUser($userToBlock, $user);

        $em
            ->expects($this->once())
            ->method('remove')
            ->with($blockedUser);

        $em
            ->expects($this->once())
            ->method('flush');

        $blockedUserManager = new BlockedUserManager(
            $repo,
            $em,
            $commentManager,
            $votingManager
        );

        $blockedUserManager->unblockUser($blockedUser);
    }

    /** @dataProvider findByParamsProvider */
    public function testFindByTokenAndUser(User $userToBlock, User $user, ?BlockedUser $blockedUser): void
    {
        $repo = $this->createMock(BlockedUserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $commentManager = $this->createMock(CommentManagerInterface::class);
        $votingManager = $this->createMock(VotingManagerInterface::class);

        $repo
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['blockedUser' => $userToBlock, 'user' => $user])
            ->willReturn($blockedUser);

        $blockedUserManager = new BlockedUserManager(
            $repo,
            $em,
            $commentManager,
            $votingManager
        );

        $this->assertSame($blockedUser, $blockedUserManager->findByBlockedUserAndOwner($user, $userToBlock));
    }

    public function findByParamsProvider(): array
    {
        $userToBlock = new User();
        $user = new User();

        return [
            'return existing entity' => [$userToBlock, $user, new BlockedUser($userToBlock, $user)],
            'return null for not blocked' => [$userToBlock, $user, null],
        ];
    }
}
