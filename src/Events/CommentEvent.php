<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\Comment;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\Activity\ActivityEventInterface;
use App\Events\Activity\UserTokenEventActivity;
use Symfony\Contracts\EventDispatcher\Event;

/** @codeCoverageIgnore */
class CommentEvent extends UserTokenEventActivity
{
    protected Comment $comment;
    protected int $type;
    protected ?User $user;

    public function __construct(Comment $comment, int $type, ?User $user = null)
    {
        $this->comment = $comment;

        parent::__construct($user ?? $this->comment->getAuthor(), $comment->getPost()->getToken(), $type);
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }
}
