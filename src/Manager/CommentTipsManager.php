<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Comment;
use App\Entity\CommentTip;
use App\Entity\User;
use App\Repository\CommentTipRepository;

class CommentTipsManager implements CommentTipsManagerInterface
{
    private CommentTipRepository $repository;

    public function __construct(CommentTipRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getByUserAndComment(User $user, Comment $comment): ?CommentTip
    {
        return $this->repository->findOneBy(['user' => $user, 'comment' => $comment]);
    }

    public function getTotalFeesPerCrypto(\DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        return $this->repository->getTotalFeesPerCrypto($dateFrom, $dateTo);
    }
}
