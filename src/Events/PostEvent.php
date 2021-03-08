<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Post;
use App\Entity\Token\Token;
use Symfony\Contracts\EventDispatcher\Event;

class PostEvent extends Event implements PostEventInterface, TokenEventInterface
{
    protected Post $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getToken(): Token
    {
        return $this->post->getToken();
    }
}
