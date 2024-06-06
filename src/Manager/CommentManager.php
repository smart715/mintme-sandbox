<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Comment;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\CommentRepository;

class CommentManager implements CommentManagerInterface
{
    private CommentRepository $repository;

    private UserTokenFollowManagerInterface $userTokenFollowManager;

    public function __construct(
        CommentRepository $commentRepository,
        UserTokenFollowManagerInterface $userTokenFollowManager
    ) {
        $this->repository = $commentRepository;
        $this->userTokenFollowManager = $userTokenFollowManager;
    }

    public function getById(int $id): ?Comment
    {
        return $this->repository->find($id);
    }

    /** {@inheritDoc} */
    public function findAllByCreatorAndTokenOwner(User $creator, User $tokenOwner): array
    {
        return $this->repository->findAllByCreatorIdAndTokenOwnerProfileId(
            $creator->getId(),
            $tokenOwner->getProfile()->getId(),
        );
    }

    /** {@inheritDoc} */
    public function getRecentComments(int $page, int $max): array
    {
        return $this->repository->findRecentComments($page, $max);
    }

    /** {@inheritDoc} */
    public function getRecentCommentsByUserFeed(User $user, int $page): array
    {
        $tokenOwners = [];
        $followedTokens = $this->userTokenFollowManager->getFollowedTokens($user);

        foreach ($followedTokens as $followedToken) {
            if ($followedToken->isQuiet()) {
                continue;
            }

            $tokenOwners[] = $followedToken->getOwner();
        }

        return $this->repository->findRecentCommentsByTokenOwners(
            $tokenOwners,
            $page
        );
    }

    /** {@inheritDoc} */
    public function getCommentsByHashtag(string $hashtag, int $page): array
    {
        return $this->repository->findCommentsByHashtag($hashtag, $page);
    }
}
