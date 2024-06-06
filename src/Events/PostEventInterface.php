<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Post;
use App\Entity\User;

interface PostEventInterface
{
    public function getPost(): Post;
    public function getUser(): User;
}
