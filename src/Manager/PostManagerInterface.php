<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Post;
use App\Entity\User;

interface PostManagerInterface
{
    public function getById(int $id): ?Post;
    public function getBySlug(string $slug): ?Post;
    public function getRecentPost(User $user, int $page): array;
}
