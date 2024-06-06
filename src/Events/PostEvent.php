<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Post;
use App\Entity\User;
use App\Events\Activity\UserTokenEventActivity;

/** @codeCoverageIgnore */
class PostEvent extends UserTokenEventActivity implements PostEventInterface
{
    protected Post $post;
    protected int $type;
    protected ?User $user;

    public function __construct(Post $post, int $type, ?User $user = null)
    {
        $this->post = $post;

        parent::__construct($user ?? $post->getAuthor()->getUser(), $post->getToken(), $type);
    }

    public function getPost(): Post
    {
        return $this->post;
    }
}
