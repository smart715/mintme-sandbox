<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Entity\CommentTip;

class TipTokenEventActivity extends TokenEventActivity
{
    public const NAME = 'tip.token.activity';

    private CommentTip $commentTip;

    public function __construct(CommentTip $commentTip, int $type)
    {
        $this->commentTip = $commentTip;

        parent::__construct($commentTip->getToken(), $type);
    }

    public function getCommentTip(): CommentTip
    {
        return $this->commentTip;
    }
}
