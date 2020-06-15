<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Post;

interface PostManagerInterface
{
    public function getById(int $id): ?Post;
}