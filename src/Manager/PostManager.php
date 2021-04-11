<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;

class PostManager implements PostManagerInterface
{
    /** @var PostRepository */
    private $repository;

    public function __construct(EntityManagerInterface $em)
    {
        /** @var PostRepository $repository */
        $repository = $em->getRepository(Post::class);
        $this->repository = $repository;
    }

    public function getById(int $id): ?Post
    {
        return $this->repository->find($id);
    }

    public function getBySlug(string $slug): ?Post
    {
        return $this->repository->findOneBy(['slug' => $slug]);
    }

    public function getRecentPost(User $user, int $page): array
    {
        return $this->repository->findRecentPostsOfUser($user, $page);
    }
}
