<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Comment;
use App\Repository\CommentRepository;

class CommentManager implements CommentManagerInterface
{
    /** @var CommentRepository */
    private $repository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->repository = $commentRepository;
    }

    public function getById(int $id): ?Comment
    {
        return $this->repository->find($id);
    }
}
