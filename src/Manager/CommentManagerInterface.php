<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Comment;
use App\Entity\Token\Token;
use App\Entity\User;

interface CommentManagerInterface
{
    public function getById(int $id): ?Comment;

    /** @return Comment[] */
    public function findAllByCreatorAndTokenOwner(User $user, User $tokenOwner): array;

    /** @return Comment[] */
    public function getRecentCommentsByUserFeed(User $user, int $page): array;

    /** @return Comment[] */
    public function getRecentComments(int $page, int $max): array;

    /** @return Comment[] */
    public function getCommentsByHashtag(string $hashtag, int $page): array;
}
