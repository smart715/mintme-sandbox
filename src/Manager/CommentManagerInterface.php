<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Comment;

interface CommentManagerInterface
{
    public function getById(int $id): ?Comment;
}
