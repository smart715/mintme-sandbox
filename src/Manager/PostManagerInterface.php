<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\PostRepository;

interface PostManagerInterface
{
    public function getRepository(): PostRepository;

    public function getById(int $id): ?Post;
    public function getBySlug(string $slug): ?Post;

    /**
     * @return Post[]
     */
    public function getRecentPostsByUserFeed(User $user, int $page): array;

    /**
     * @return Post[]
     */
    public function getRecentPosts(int $page, int $max): array;

    /**
     * @return Post[]
     */
    public function getPostsCreatedAt(\DateTimeImmutable $date): array;

    /**
     * @return Post[]
     */
    public function getPostsCreatedAtByToken(
        Token $token,
        \DateTimeImmutable $date,
        bool $includeDeleted = false
    ): array;

    /**
     * @return Post[]
     */
    public function getPostsByToken(Token $token, int $offset, int $limit): array;

    public function getTokenPostsCount(Token $token): int;

    public function getActivePostsByToken(Token $token, int $offset, int $limit): array;

    public function getActivePostsCountByToken(Token $token): int;

    /**
     * @return Post[]
     */
    public function getPostsByHashtag(string $hashtag, int $page): array;
}
