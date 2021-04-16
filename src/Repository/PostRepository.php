<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class PostRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function findRecentPostsOfUser(User $user, int $page = 0, int $max = 10): array
    {
        return $this->createQueryBuilder('post')
            ->where('post.token IN (:tokens)')
            ->setParameter('tokens', $user->getTokens())
            ->orderBy('post.createdAt', 'ASC')
            ->setFirstResult($page * $max)
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();
    }
}
