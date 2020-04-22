<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;

class PostManager implements PostManagerInterface
{
    /** @var PostRepository */
    private $repository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repository = $em->getRepository(Post::class);
    }

    public function getById(int $id): ?Post
    {
        return $this->repository->find($id);
    }
}
