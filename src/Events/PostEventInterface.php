<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Post;

interface PostEventInterface
{
    public function getPost(): Post;
}
