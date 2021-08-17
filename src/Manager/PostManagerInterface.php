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
     * @param User $user
     * @param int $page
     * @return Post[]
     */
    public function getRecentPost(User $user, int $page): array;

    /**
     * @return Post[]
     */
    public function getPostsCreatedAt(\DateTimeImmutable $date): array;

    /**
     * @return Post[]
     */
    public function getPostsCreatedAtByToken(Token $token, \DateTimeImmutable $date): array;
}
