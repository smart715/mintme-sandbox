<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Comment;
use App\Entity\CommentTip;
use App\Entity\User;

interface CommentTipsManagerInterface
{
    public function getByUserAndComment(User $user, Comment $comment): ?CommentTip;
    public function getTotalFeesPerCrypto(\DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array;
}
