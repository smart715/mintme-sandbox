<?php declare(strict_types = 1);

namespace App\Repository\News;

use App\Entity\News\Post;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\NewsBundle\Entity\BasePostRepository;

/** @codeCoverageIgnore */
class PostRepository extends BasePostRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(Post::class));
    }

    public function getRandomPost(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->orderBy('RAND()')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
