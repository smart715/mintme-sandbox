<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;

interface PostManagerInterface
{
    public function getById(int $id): ?Post;
    public function getBySlug(string $slug): ?Post;

    /**
     * @param User $user
     * @param int $page
     * @return Post[]
     */
    public function getRecentPost(User $user, int $page): array;
    public function getCreatedPostsToday(): array;
    public function getCreatedPostsTodayByToken(Token $token): array;
}
