<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class PostRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function findRecentPostsOfUser(User $user, int $page): array
    {
        return $this->createQueryBuilder('post')
            ->where('post.token IN (:tokens)')
            ->setParameter('tokens', $user->getTokens())
            ->orderBy('post.createdAt', 'ASC')
            ->setFirstResult($page * 2)
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();
    }
}
