<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\BlockedUser;
use App\Entity\User;
use App\Repository\BlockedUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class BlockedUserManager implements BlockedUserManagerInterface
{
    private BlockedUserRepository $repository;
    private EntityManagerInterface $entityManager;
    private CommentManagerInterface $commentManager;
    private VotingManagerInterface $votingManager;

    public function __construct(
        BlockedUserRepository $repository,
        EntityManagerInterface $entityManager,
        CommentManagerInterface $commentManager,
        VotingManagerInterface $votingManager
    ) {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->commentManager = $commentManager;
        $this->votingManager = $votingManager;
    }

    public function blockUser(User $user, User $userToBlock, bool $removeActions = false): void
    {
        $blockedUser = new BlockedUser($user, $userToBlock);

        if ($removeActions) {
            $this->removeActions($user, $userToBlock);
        }

        $this->entityManager->persist($blockedUser);
        $this->entityManager->flush();
    }

    public function unblockUser(BlockedUser $blockedUser): void
    {
        $this->entityManager->remove($blockedUser);
        $this->entityManager->flush();
    }

    public function findByBlockedUserAndOwner(User $user, User $blockedUser): ?BlockedUser
    {
        /** @var BlockedUser|null $entity */
        $entity = $this->repository->findOneBy(['user' => $user, 'blockedUser' => $blockedUser]);

        return $entity;
    }

    private function removeActions(User $user, User $blockedUser): void
    {
        $votings = $this->votingManager->getAllCreatedByUserAndTokenOwner($blockedUser, $user);

        foreach ($votings as $voting) {
            $this->entityManager->remove($voting);
        }

        $comments = $this->commentManager->findAllByCreatorAndTokenOwner($blockedUser, $user);

        foreach ($comments as $comment) {
            $this->entityManager->remove($comment);
        }
    }
}
